<?php

namespace App\Webhook\Handler;

use App\Webhook\Service\PayloadUserProvisioner;
use Psr\Log\LoggerInterface;

class SubscriptionCreatedHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PayloadUserProvisioner $provisioner,
    ) {
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
        $customer = is_array($data['customer'] ?? null) ? $data['customer'] : [];
        $customerId = $data['customer_id'] ?? ($customer['id'] ?? null);

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $first = is_array($items[0] ?? null) ? $items[0] : [];
        $price = is_array($first['price'] ?? null) ? $first['price'] : [];
        $priceId = $price['id'] ?? ($data['price_id'] ?? null);

        $trial = is_array($data['trial'] ?? null) ? $data['trial'] : [];
        $trialEnd = $trial['ends_at'] ?? ($data['trial_end'] ?? null);

        $period = is_array($data['current_billing_period'] ?? null) ? $data['current_billing_period'] : [];
        $currentEnd = $period['ends_at'] ?? ($data['current_period_end'] ?? null);

        $this->logger->info('Paddle: subscription_created', [
            'subscription_id' => $payload['id'] ?? null,
            'customer_id' => $customerId,
            'price_id' => $priceId,
            'trial_end' => $trialEnd,
            'current_period_end' => $currentEnd,
        ]);

        // Provision into Payload CMS
        $this->provisioner->handleSubscriptionCreated($payload);
    }
}
