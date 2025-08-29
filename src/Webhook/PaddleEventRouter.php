<?php

namespace App\Webhook;

use App\Webhook\Handler\PaymentFailedHandler;
use App\Webhook\Handler\PaymentSucceededHandler;
use App\Webhook\Handler\SubscriptionCanceledHandler;
use App\Webhook\Handler\SubscriptionCreatedHandler;
use App\Webhook\Handler\SubscriptionUpdatedHandler;
use Psr\Log\LoggerInterface;

/**
 * Router pour les événements webhook Paddle
 * Redirige les événements vers les handlers appropriés
 * Gère les exceptions et les erreurs
 */
class PaddleEventRouter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SubscriptionCreatedHandler $subscriptionCreatedHandler,
        private readonly PaymentSucceededHandler $paymentSucceededHandler,
        private readonly SubscriptionUpdatedHandler $subscriptionUpdatedHandler,
        private readonly PaymentFailedHandler $paymentFailedHandler,
        private readonly SubscriptionCanceledHandler $subscriptionCanceledHandler,
    ) {
    }

    /**
     * Route a Paddle event payload by its event_type.
     * For now, handlers only log the event. No business logic executed yet.
     *
     * @param string|null $eventType Type d'événement à router
     * @param array<string, mixed>|null $payload Payload de l'événement
     * @throws \RuntimeException Si le traitement de l'événement échoue
     */
    public function route(?string $eventType, ?array $payload): void
    {
        if (!is_array($payload)) {
            $message = 'Paddle router received non-array payload';
            $this->logger->warning($message);
            throw new \RuntimeException($message);
        }

        if (!is_string($eventType) || $eventType === '') {
            $eventType = $payload['event_type'] ?? null;

            if (!is_string($eventType) || $eventType === '') {
                $message = 'Paddle router missing event_type';
                $this->logger->warning($message, [
                    'keys' => array_keys($payload),
                ]);
                throw new \RuntimeException($message);
            }
        }

        try {
            switch ($eventType) {
                case 'subscription_created':
                    $this->subscriptionCreatedHandler->handle($payload);
                    break;
                case 'payment_succeeded':
                    $this->paymentSucceededHandler->handle($payload);
                    break;
                case 'subscription_updated':
                    $this->subscriptionUpdatedHandler->handle($payload);
                    break;
                case 'payment_failed':
                    $this->paymentFailedHandler->handle($payload);
                    break;
                case 'subscription_canceled':
                    $this->subscriptionCanceledHandler->handle($payload);
                    break;
                default:
                    $this->logger->info('Paddle router: unhandled event_type', [
                        'event_type' => $eventType,
                    ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Error processing Paddle event', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Propager l'exception pour que le service de retry puisse la gérer
            throw new \RuntimeException(
                sprintf('Failed to process Paddle event %s: %s', $eventType, $e->getMessage()),
                0,
                $e
            );
        }
    }
}
