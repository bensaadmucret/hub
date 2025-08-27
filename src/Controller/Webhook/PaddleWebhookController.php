<?php

namespace App\Controller\Webhook;

use App\Entity\PaddleWebhookEvent;
use App\Repository\PaddleWebhookEventRepository;
use App\Security\PaddleSignatureVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Webhook\PaddleEventRouter;

class PaddleWebhookController extends AbstractController
{
    #[Route('/webhooks/paddle', name: 'webhook_paddle', methods: ['POST'])]
    public function __invoke(
        Request $request,
        LoggerInterface $logger,
        PaddleSignatureVerifier $signatureVerifier,
        PaddleWebhookEventRepository $eventRepo,
        EntityManagerInterface $em,
        PaddleEventRouter $router,
    ): Response {
        // 0) Read raw body and signature header
        $raw = $request->getContent();
        $providedSignature = $request->headers->get('paddle-signature'); // case-insensitive

        // 1) Signature verification (HMAC - Paddle Billing)
        if (!$signatureVerifier->isValid($providedSignature, $raw)) {
            $logger->warning('Paddle webhook signature invalid or missing');
            return new Response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        // 2) Decode JSON to extract event info (still ACK fast if it fails)
        $decoded = null;
        try {
            $decoded = $raw !== '' ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
        } catch (\Throwable $e) {
            $logger->warning('Paddle webhook JSON decode failed (after valid signature)', [
                'error' => $e->getMessage(),
                'raw' => mb_substr($raw, 0, 1000),
            ]);
        }

        // 3) Determine event id and type
        $eventId = $request->headers->get('paddle-event-id');
        if (!$eventId && is_array($decoded) && array_key_exists('id', $decoded)) {
            $rawId = $decoded['id'];
            if (is_string($rawId) || is_int($rawId)) {
                $eventId = (string) $rawId;
            }
        }

        $eventType = null;
        if (is_array($decoded) && array_key_exists('event_type', $decoded)) {
            $rawType = $decoded['event_type'];
            if (is_string($rawType)) {
                $eventType = $rawType;
            }
        }

        if (!$eventId) {
            // Without an ID we can't do idempotence; still ACK but warn
            $logger->warning('Paddle webhook missing event id', [
                'event_type' => $eventType,
            ]);
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        // 4) Idempotence check
        $existing = $eventRepo->findOneByEventId($eventId);
        if ($existing) {
            $logger->info('Paddle webhook duplicate event ignored', [
                'event_id' => $eventId,
                'event_type' => $existing->getEventType(),
            ]);
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        // 5) Persist event as received
        $event = new PaddleWebhookEvent($eventId, $eventType, 'received');
        $em->persist($event);
        $em->flush();

        // 6) Minimal routing (log only for now)
        $logger->info('Paddle webhook received', [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);

        // Route the event to log-only handlers (no business logic yet)
        $payload = null;
        if (is_array($decoded)) {
            /** @var array<string,mixed> $decoded */
            $payload = $decoded;
        }
        $router->route($payload);

        // Always ACK fast
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
