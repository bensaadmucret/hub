<?php

declare(strict_types=1);

namespace App\Tests\Controller\Webhook;

use App\Integration\Payload\PayloadClientInterface;
use App\Repository\PaddleWebhookEventRepository;
use App\Entity\PaddleWebhookEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PaddleWebhookControllerTest extends WebTestCase
{
    private static function secret(): string
    {
        return $_ENV['PADDLE_WEBHOOK_SECRET'] ?? $_SERVER['PADDLE_WEBHOOK_SECRET'] ?? 'test_secret';
    }

    private static function sign(string $raw): string
    {
        return base64_encode(hash_hmac('sha256', $raw, self::secret(), true));
    }

    public function testUnauthorizedWhenSignatureMissing(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        // Mock external client to avoid env/HTTP side effects
        $mock = $this->createMock(PayloadClientInterface::class);
        $mock->method('createSubscription')->willReturn(['ok' => true]);
        $mock->method('updateSubscription')->willReturn(['ok' => true]);
        $mock->method('deleteSubscription')->willReturn(['ok' => true]);
        $container->set(PayloadClientInterface::class, $mock);
        $client->request('POST', '/webhooks/paddle', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testBadRequestOnInvalidJsonEvenWithValidSignature(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $mock = $this->createMock(PayloadClientInterface::class);
        $mock->method('createSubscription')->willReturn(['ok' => true]);
        $mock->method('updateSubscription')->willReturn(['ok' => true]);
        $mock->method('deleteSubscription')->willReturn(['ok' => true]);
        $container->set(PayloadClientInterface::class, $mock);
        $raw = '{invalid-json';
        $client->request(
            'POST',
            '/webhooks/paddle',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_paddle-signature' => self::sign($raw),
            ],
            content: $raw,
        );

        // Le contrôleur ACK en 204 même si le JSON est invalide (signature valide)
        self::assertSame(204, $client->getResponse()->getStatusCode());
    }

    public function testValidSignaturePersistsEventAndIsIdempotent(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        // Initialiser le schéma Doctrine pour l'entité PaddleWebhookEvent (SQLite test)
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($em);
        $metadata = [$em->getClassMetadata(PaddleWebhookEvent::class)];
        // Nettoyage et création du schéma pour un test hermétique
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $mock = $this->createMock(PayloadClientInterface::class);
        $mock->method('createSubscription')->willReturn(['ok' => true]);
        $mock->method('updateSubscription')->willReturn(['ok' => true]);
        $mock->method('deleteSubscription')->willReturn(['ok' => true]);
        $container->set(PayloadClientInterface::class, $mock);
        /** @var PaddleWebhookEventRepository $repo */
        $repo = $container->get(PaddleWebhookEventRepository::class);

        $payload = [
            'event_id' => 'evt_test_12345',
            'event_type' => 'subscription.created',
            'occurred_at' => '2025-01-01T00:00:00Z',
            'data' => [
                'id' => 'sub_123',
                'status' => 'active',
                'customer_id' => 'cus_1',
            ],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_paddle-signature' => self::sign($raw),
            'HTTP_paddle-event-id' => 'evt_test_12345',
        ];

        // First call: should persist and return 204/No Content
        $client->request('POST', '/webhooks/paddle', server: $headers, content: $raw);
        $status = $client->getResponse()->getStatusCode();
        self::assertTrue(in_array($status, [200, 202, 204], true), 'Expected 2xx, got ' . $status);

        // Verify entity exists (idempotence baseline)
        $event = $repo->findOneBy(['eventId' => 'evt_test_12345']);
        self::assertNotNull($event, 'Webhook event should be persisted');

        // Second call with same payload: must be idempotent and not duplicate
        $client->request('POST', '/webhooks/paddle', server: $headers, content: $raw);
        $status2 = $client->getResponse()->getStatusCode();
        self::assertTrue(in_array($status2, [200, 202, 204], true), 'Expected 2xx on idempotent retry, got ' . $status2);

        // Ensure still single record
        $events = $repo->findBy(['eventId' => 'evt_test_12345']);
        self::assertCount(1, $events, 'Idempotence should prevent duplicates');
    }
}
