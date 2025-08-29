<?php

namespace App\Controller\Webhook;

use App\Entity\PaddleWebhookEvent;
use App\Repository\PaddleWebhookEventRepository;
use App\Security\PaddleSignatureVerifier;
use App\Webhook\PaddleEventRouter;
use App\Webhook\PaddleWebhookRetryService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur unifié pour les webhooks Paddle
 * Gère la validation des signatures, la persistance des événements et le routage
 */
class PaddleWebhookController extends AbstractController
{
    /**
     * Endpoint principal pour les webhooks Paddle
     * Persiste les événements et assure l'idempotence
     */
    #[Route('/webhooks/paddle', name: 'webhook_paddle_events', methods: ['POST'])]
    public function __invoke(
        Request $request,
        LoggerInterface $logger,
        PaddleSignatureVerifier $signatureVerifier,
        PaddleWebhookEventRepository $eventRepo,
        EntityManagerInterface $em,
        PaddleEventRouter $router,
        PaddleWebhookRetryService $retryService,
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

        // 5) Persist event as received with payload
        $jsonPayload = null;
        if (is_array($decoded)) {
            try {
                $jsonPayload = json_encode($decoded, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $logger->warning('Failed to encode webhook payload', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $event = new PaddleWebhookEvent($eventId, $eventType, 'received', $jsonPayload);
        $em->persist($event);
        $em->flush();

        // 6) Log reception
        $logger->info('Paddle webhook received', [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);

        // 7) Process asynchronously (ACK fast, process later)
        // Nous accusons réception immédiatement pour éviter les timeouts
        // Le traitement sera fait en arrière-plan ou via une commande

        // Optionnel: traitement synchrone pour les environnements de développement
        // Dans un environnement de production, on pourrait utiliser un worker asynchrone
        try {
            // Traitement synchrone pour le développement
            if (is_array($decoded) && $eventType) {
                $event->setStatus('processing');
                $em->flush();

                $retryService->processEvent($event);
            }
        } catch (\Throwable $e) {
            // Les erreurs sont gérées par le service de retry
            // Pas besoin de faire quoi que ce soit ici
            $logger->info('Webhook processing deferred to retry mechanism', [
                'event_id' => $eventId,
            ]);
        }

        // Always ACK fast
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Route de compatibilité pour l'ancien endpoint (singulier)
     * Redirige vers le nouvel endpoint (pluriel)
     */
    #[Route('/webhook/paddle', name: 'webhook_paddle', methods: ['POST'])]
    public function legacyEndpoint(Request $request): Response
    {
        // Simplement déléguer au nouvel endpoint
        return $this->forward(self::class . '::__invoke', [
            'request' => $request,
        ]);
    }

    /**
     * Traite un événement Paddle spécifique selon son type
     * Méthode de compatibilité avec l'ancien contrôleur
     */
    private function handlePaddleEvent(string $eventType, array $data, LoggerInterface $logger): void
    {
        switch ($eventType) {
            case 'transaction.completed':
                $this->handleTransactionCompleted($data, $logger);
                break;
            case 'subscription.created':
                $this->handleSubscriptionCreated($data, $logger);
                break;
            case 'subscription.updated':
                $this->handleSubscriptionUpdated($data, $logger);
                break;
            case 'subscription.canceled':
                $this->handleSubscriptionCanceled($data, $logger);
                break;
            default:
                $logger->info('Événement Paddle non géré', ['event_type' => $eventType]);
        }
    }

    private function handleTransactionCompleted(array $data, LoggerInterface $logger): void
    {
        $transactionData = $data['data'] ?? [];
        $transactionId = $transactionData['id'] ?? null;
        $customerId = $transactionData['customer_id'] ?? null;
        $status = $transactionData['status'] ?? null;

        $logger->info('Transaction Paddle complétée', [
            'transaction_id' => $transactionId,
            'customer_id' => $customerId,
            'status' => $status,
        ]);

        // TODO: Logique métier pour activer l'abonnement utilisateur
    }

    private function handleSubscriptionCreated(array $data, LoggerInterface $logger): void
    {
        $subscriptionData = $data['data'] ?? [];
        $subscriptionId = $subscriptionData['id'] ?? null;
        $customerId = $subscriptionData['customer_id'] ?? null;
        $status = $subscriptionData['status'] ?? null;

        $logger->info('Souscription Paddle créée', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'status' => $status,
        ]);

        // TODO: Logique métier pour créer l'abonnement utilisateur
    }

    private function handleSubscriptionUpdated(array $data, LoggerInterface $logger): void
    {
        $subscriptionData = $data['data'] ?? [];
        $subscriptionId = $subscriptionData['id'] ?? null;
        $status = $subscriptionData['status'] ?? null;

        $logger->info('Souscription Paddle mise à jour', [
            'subscription_id' => $subscriptionId,
            'status' => $status,
        ]);

        // TODO: Logique métier pour mettre à jour l'abonnement
    }

    private function handleSubscriptionCanceled(array $data, LoggerInterface $logger): void
    {
        $subscriptionData = $data['data'] ?? [];
        $subscriptionId = $subscriptionData['id'] ?? null;

        $logger->info('Souscription Paddle annulée', [
            'subscription_id' => $subscriptionId,
        ]);

        // TODO: Logique métier pour désactiver l'abonnement utilisateur
    }
}
