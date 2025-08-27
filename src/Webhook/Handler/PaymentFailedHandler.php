<?php

namespace App\Webhook\Handler;

use App\Webhook\Service\PayloadUserProvisioner;
use Psr\Log\LoggerInterface;

class PaymentFailedHandler
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
        $subscriptionId = $data['subscription_id'] ?? ($subscription['id'] ?? null);

        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];
        $amount = $data['amount'] ?? ($totals['total'] ?? null);

        $this->logger->info('Paddle: payment_failed', [
            'subscription_id' => $subscriptionId,
            'failed_at' => $data['failed_at'] ?? ($data['billed_at'] ?? null),
            'attempt_count' => $data['attempt_count'] ?? null,
            'amount' => $amount,
            'currency' => $data['currency_code'] ?? ($data['currency'] ?? null),
        ]);

        // Provision into Payload CMS
        $this->provisioner->handlePaymentFailed($payload);
    }
}
