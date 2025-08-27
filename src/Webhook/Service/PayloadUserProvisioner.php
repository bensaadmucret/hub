<?php

namespace App\Webhook\Service;

use App\Core\Service\PayloadClient;
use Psr\Log\LoggerInterface;

class PayloadUserProvisioner
{
    public function __construct(
        private readonly PayloadClient $payload,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleSubscriptionCreated(array $payload): void
    {
        $this->upsertFromPayload('subscription_created', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handlePaymentSucceeded(array $payload): void
    {
        $this->upsertFromPayload('payment_succeeded', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleSubscriptionUpdated(array $payload): void
    {
        $this->upsertFromPayload('subscription_updated', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handlePaymentFailed(array $payload): void
    {
        $this->upsertFromPayload('payment_failed', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleSubscriptionCanceled(array $payload): void
    {
        $this->upsertFromPayload('subscription_canceled', $payload);
    }

    /**
     * Upsert une souscription à partir d'un payload Paddle.
     *
     * @param array<string, mixed> $payload
     */
    private function upsertFromPayload(string $eventType, array $payload): void
    {
        try {
            $data = $payload['data'] ?? [];
            if (!is_array($data)) {
                $data = [];
            }
            /** @var array<string, mixed> $data */

            $subscriptionId = $payload['id'] ?? ($data['id'] ?? null);
            if (!is_string($subscriptionId) || $subscriptionId === '') {
                $this->logger->warning('Provisioner: missing subscriptionId', ['eventType' => $eventType]);
                return;
            }

            $customer = is_array(($data['customer'] ?? null)) ? $data['customer'] : null;
            $customerId = $data['customer_id'] ?? ($customer['id'] ?? null);
            $email = ($customer['email'] ?? null) ?: ($data['customer_email'] ?? null);

            $userId = $this->findOrCreateUserId($email);
            if ($userId === null) {
                $this->logger->warning('Provisioner: unable to resolve user for subscription', [
                    'eventType' => $eventType,
                    'subscriptionId' => $subscriptionId,
                    'email' => $email,
                    'customerId' => $customerId,
                ]);
                return;
            }

            // Secure nested data access
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $firstItem = is_array($items[0] ?? null) ? $items[0] : null;
            $price = is_array($firstItem['price'] ?? null) ? $firstItem['price'] : null;

            $priceId = ($price['id'] ?? null) ?: ($data['price_id'] ?? null);
            $productId = ($price['product_id'] ?? null) ?: ($data['product_id'] ?? null);

            $trialArr = is_array($data['trial'] ?? null) ? $data['trial'] : null;
            $trialEnd = ($trialArr['ends_at'] ?? null) ?: ($data['trial_end'] ?? null);

            $currPeriod = is_array($data['current_billing_period'] ?? null) ? $data['current_billing_period'] : null;
            $currentPeriodEnd = ($currPeriod['ends_at'] ?? null) ?: ($data['current_period_end'] ?? null);

            $cancelAtPeriodEnd = (bool)($data['cancel_at_period_end'] ?? false);
            $lastPaymentAt = $data['last_payment_at'] ?? null;

            // Amount / currency best effort (fields differ by event type)
            $amount = ($data['amount'] ?? null) ?: ($price['unit_price'] ?? null);
            $currency = ($data['currency_code'] ?? null) ?: ($data['currency'] ?? null);

            // Determine status from event
            /** @var array<string, mixed> $dataArr */
            $dataArr = $data;
            $status = $this->deriveStatus($eventType, $dataArr, is_string($trialEnd) ? $trialEnd : null);

            // Fetch existing subscription by subscriptionId
            $existing = $this->payload->get('subscriptions', [
                'where' => [
                    'subscriptionId' => [ 'equals' => $subscriptionId ],
                ],
                'limit' => 1,
            ]);

            $docs = is_array($existing['docs'] ?? null) ? $existing['docs'] : [];
            $isNew = count($docs) === 0;

            $base = [
                'user' => $userId,
                'provider' => 'paddle',
                'customerId' => $customerId,
                'subscriptionId' => $subscriptionId,
                'productId' => $productId,
                'priceId' => $priceId,
                'status' => $status,
                'trialEnd' => $trialEnd,
                'currentPeriodEnd' => $currentPeriodEnd,
                'cancelAtPeriodEnd' => $cancelAtPeriodEnd || $eventType === 'subscription_canceled',
                'lastPaymentAt' => $lastPaymentAt,
                'amount' => $amount,
                'currency' => $currency,
            ];

            $historyEntry = [
                'type' => $eventType,
                'occurredAt' => $payload['occurred_at'] ?? ($payload['occurredAt'] ?? (new \DateTimeImmutable())->format(DATE_ATOM)),
                'raw' => $this->sanitizeRaw($payload),
            ];

            if ($isNew) {
                $base['history'] = [$historyEntry];
                $this->payload->post('subscriptions', $base);
            } else {
                $sub = is_array($docs[0] ?? null) ? $docs[0] : null;
                if (!is_array($sub)) {
                    $this->logger->warning('Provisioner: invalid existing subscription payload', ['subscriptionId' => $subscriptionId]);
                    return;
                }
                $subId = $sub['id'] ?? null;
                if (!is_string($subId) || $subId === '') {
                    $this->logger->warning('Provisioner: existing subscription without id', ['subscriptionId' => $subscriptionId]);
                    return;
                }
                $existingHistory = is_array($sub['history'] ?? null) ? $sub['history'] : [];
                $patch = $base;
                $patch['history'] = array_merge($existingHistory, [$historyEntry]);
                $this->payload->patch('subscriptions/' . $subId, $patch);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Provisioner error', [
                'eventType' => $eventType,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function findOrCreateUserId(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        try {
            $found = $this->payload->get('users', [
                'where' => [ 'email' => [ 'equals' => $email ] ],
                'limit' => 1,
            ]);
            $docs = is_array($found['docs'] ?? null) ? $found['docs'] : [];
            if (!empty($docs)) {
                $first = is_array($docs[0] ?? null) ? $docs[0] : null;
                $id = $first['id'] ?? null;
                return is_string($id) ? $id : null;
            }

            // Try to create minimal user. Depending on Payload config, password may be required.
            $created = $this->payload->post('users', [
                'email' => $email,
            ]);
            $doc = is_array($created['doc'] ?? null) ? $created['doc'] : null;
            $id = ($doc['id'] ?? null) ?: ($created['id'] ?? null);
            return is_string($id) ? $id : null;
        } catch (\Throwable $e) {
            $this->logger->error('findOrCreateUserId error', ['email' => $email, 'message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function deriveStatus(string $eventType, array $data, ?string $trialEnd): string
    {
        $now = new \DateTimeImmutable();
        $trialEndDt = null;
        if (is_string($trialEnd)) {
            try { $trialEndDt = new \DateTimeImmutable($trialEnd); } catch (\Throwable) {}
        }

        return match ($eventType) {
            'subscription_created' => ($trialEndDt && $trialEndDt > $now) ? 'trialing' : 'active',
            'payment_succeeded' => 'active',
            'payment_failed' => 'past_due',
            'subscription_canceled' => 'canceled',
            'subscription_updated' => $this->mapUpdatedStatus($data) ?? 'active',
            default => 'active',
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapUpdatedStatus(array $data): ?string
    {
        $status = $data['status'] ?? null;
        if (!is_string($status)) {
            return null;
        }
        $status = strtolower($status);
        return match ($status) {
            'active' => 'active',
            'trialing', 'trial' => 'trialing',
            'past_due', 'past-due', 'pastdue' => 'past_due',
            'canceled', 'cancelled' => 'canceled',
            default => null,
        };
    }

    /**
     * Sanitize raw payload to avoid huge storage / secrets
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizeRaw(array $payload): array
    {
        // Remove sensitive headers or signatures if present at root
        unset($payload['signature']);
        return $payload;
    }
}
