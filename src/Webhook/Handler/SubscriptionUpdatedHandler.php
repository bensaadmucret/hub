<?php

namespace App\Webhook\Handler;

use App\Webhook\Service\PayloadUserProvisioner;
use Psr\Log\LoggerInterface;

class SubscriptionUpdatedHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PayloadUserProvisioner $provisioner,
    )
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        $data = $payload['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }

        $subscription = is_array($data['subscription'] ?? null) ? $data['subscription'] : [];
        $subscriptionId = $data['id'] ?? ($subscription['id'] ?? ($payload['id'] ?? null));

        $cancellation = is_array($data['cancellation'] ?? null) ? $data['cancellation'] : [];
        $cancelAtPeriodEnd = $data['cancel_at_period_end'] ?? ($cancellation['effective_immediately'] ?? null);

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $first = is_array($items[0] ?? null) ? $items[0] : [];
        $price = is_array($first['price'] ?? null) ? $first['price'] : [];
        $priceId = $price['id'] ?? ($data['price_id'] ?? null);

        $period = is_array($data['current_billing_period'] ?? null) ? $data['current_billing_period'] : [];
        $currentEnd = $period['ends_at'] ?? ($data['current_period_end'] ?? null);

        $this->logger->info('Paddle: subscription_updated', [
            'subscription_id' => $subscriptionId,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
            'price_id' => $priceId,
            'current_period_end' => $currentEnd,
            'status' => $data['status'] ?? null,
        ]);

        // Provision into Payload CMS
        $this->provisioner->handleSubscriptionUpdated($payload);
    }
}

