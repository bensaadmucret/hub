<?php

namespace App\Webhook;

use App\Webhook\Handler\PaymentFailedHandler;
use App\Webhook\Handler\PaymentSucceededHandler;
use App\Webhook\Handler\SubscriptionCanceledHandler;
use App\Webhook\Handler\SubscriptionCreatedHandler;
use App\Webhook\Handler\SubscriptionUpdatedHandler;
use Psr\Log\LoggerInterface;

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
     * @param array<string, mixed>|null $payload
     */
    public function route(?array $payload): void
    {
        if (!is_array($payload)) {
            $this->logger->warning('Paddle router received non-array payload');
            return;
        }

        $eventType = $payload['event_type'] ?? null;
        if (!is_string($eventType) || $eventType === '') {
            $this->logger->warning('Paddle router missing event_type', [
                'keys' => array_keys($payload),
            ]);
            return;
        }

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
    }
}
