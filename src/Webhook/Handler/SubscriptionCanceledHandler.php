<?php

namespace App\Webhook\Handler;

use App\Webhook\Service\PayloadUserProvisioner;
use Psr\Log\LoggerInterface;

class SubscriptionCanceledHandler
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
        $subscription = is_array($data['subscription'] ?? null) ? $data['subscription'] : [];
        $subscriptionId = $data['id'] ?? ($subscription['id'] ?? ($payload['id'] ?? null));

        $period = is_array($data['current_billing_period'] ?? null) ? $data['current_billing_period'] : [];
        $effectiveAt = $data['cancellation_effective_at'] ?? ($period['ends_at'] ?? null);

        $this->logger->info('Paddle: subscription_canceled', [
            'subscription_id' => $subscriptionId,
            'effective_at' => $effectiveAt,
            'cancel_at_period_end' => $data['cancel_at_period_end'] ?? true,
        ]);

        // Provision into Payload CMS
        $this->provisioner->handleSubscriptionCanceled($payload);
    }
}
