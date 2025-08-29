<?php

namespace App\Tests\Webhook;

use App\Entity\PaddleWebhookEvent;
use App\Repository\PaddleWebhookEventRepository;
use App\Webhook\PaddleEventRouter;
use App\Webhook\PaddleWebhookRetryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PaddleWebhookRetryServiceTest extends TestCase
{
    private PaddleWebhookRetryService $retryService;
    private EntityManagerInterface|MockObject $entityManager;
    private PaddleEventRouter|MockObject $eventRouter;
    private PaddleWebhookEventRepository|MockObject $repository;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventRouter = $this->createMock(PaddleEventRouter::class);
        $this->repository = $this->createMock(PaddleWebhookEventRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->retryService = new PaddleWebhookRetryService(
            $this->entityManager,
            $this->repository,
            $this->eventRouter,
            $this->logger,
            'test'
        );
    }

    public function testProcessEventSuccess(): void
    {
        // Créer un événement webhook
        $event = new PaddleWebhookEvent('evt_test_123', 'subscription.created', 'processing');
        $event->setPayload('{"event_type":"subscription.created","data":{"id":"sub_123"}}');

        // Configurer le comportement du routeur
        $this->eventRouter->expects($this->once())
            ->method('route')
            ->with('subscription.created', $this->anything());

        // Configurer le comportement de l'entity manager
        // Le service appelle flush() deux fois : une fois pour mettre à jour le statut, une fois après le traitement
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        // Exécuter la méthode à tester
        $this->retryService->processEvent($event);

        // Vérifier que l'événement a été correctement traité
        $this->assertEquals('processed', $event->getStatus());
    }

    public function testProcessEventFailure(): void
    {
        // Créer un événement webhook
        $event = new PaddleWebhookEvent('evt_test_123', 'subscription.created', 'processing');
        $event->setPayload('{"event_type":"subscription.created","data":{"id":"sub_123"}}');

        // Configurer le comportement du routeur pour simuler une erreur
        $this->eventRouter->expects($this->once())
            ->method('route')
            ->with('subscription.created', $this->anything())
            ->willThrowException(new \RuntimeException('Test error'));

        // Configurer le comportement de l'entity manager
        // Le service appelle flush() deux fois : une fois pour mettre à jour le statut, une fois après le traitement
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        // Exécuter la méthode à tester
        $this->retryService->processEvent($event);

        // Vérifier que l'événement a été marqué comme échoué
        $this->assertEquals('retry_scheduled', $event->getStatus());
        $this->assertEquals(1, $event->getRetryCount());
        $this->assertNotNull($event->getNextRetryAt());
        $this->assertNotNull($event->getLastAttemptAt());
        $this->assertStringContainsString('Test error', $event->getErrorMessage());
    }

    public function testProcessScheduledRetries(): void
    {
        // Créer des événements programmés pour retry
        $event1 = new PaddleWebhookEvent('evt_test_123', 'subscription.created', 'retry_scheduled');
        $event1->setPayload('{"event_type":"subscription.created","data":{"id":"sub_123"}}');
        
        $event2 = new PaddleWebhookEvent('evt_test_456', 'subscription.updated', 'retry_scheduled');
        $event2->setPayload('{"event_type":"subscription.updated","data":{"id":"sub_456"}}');

        // Configurer le comportement du repository
        $this->repository->expects($this->once())
            ->method('findScheduledForRetry')
            ->willReturn([$event1, $event2]);

        // Configurer le comportement du routeur pour le premier événement
        $this->eventRouter->expects($this->atLeast(2))
            ->method('route')
            ->willReturnCallback(function($eventType, $payload) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    $this->assertEquals('subscription.created', $eventType);
                } elseif ($callCount === 2) {
                    $this->assertEquals('subscription.updated', $eventType);
                }
                
                return null;
            });

        // Configurer le comportement de l'entity manager
        // Pour chaque événement, flush() est appelé 2 fois
        $this->entityManager->expects($this->exactly(4))
            ->method('flush');

        // Exécuter la méthode à tester
        $count = $this->retryService->processScheduledRetries();

        // Vérifier que les deux événements ont été traités
        $this->assertEquals(2, $count);
        $this->assertEquals('processed', $event1->getStatus());
        $this->assertEquals('processed', $event2->getStatus());
    }

    public function testMaxRetriesReached(): void
    {
        // Créer un événement qui a atteint le nombre maximum de tentatives
        $event = new PaddleWebhookEvent('evt_test_123', 'subscription.created', 'retry_scheduled');
        $event->setPayload('{"event_type":"subscription.created","data":{"id":"sub_123"}}');
        
        // Simuler 5 tentatives précédentes
        for ($i = 0; $i < 5; $i++) {
            $event->incrementRetryCount();
        }

        // Configurer le comportement du routeur pour simuler une erreur
        $this->eventRouter->expects($this->once())
            ->method('route')
            ->with('subscription.created', $this->anything())
            ->willThrowException(new \RuntimeException('Test error'));

        // Configurer le comportement de l'entity manager
        // Le service appelle flush() deux fois : une fois pour mettre à jour le statut, une fois après le traitement
        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        // Exécuter la méthode à tester
        $this->retryService->processEvent($event);

        // Vérifier que l'événement a été marqué comme échoué définitivement
        $this->assertEquals('failed', $event->getStatus());
        $this->assertEquals(6, $event->getRetryCount());
        $this->assertNull($event->getNextRetryAt());
        $this->assertNotNull($event->getLastAttemptAt());
    }
}
