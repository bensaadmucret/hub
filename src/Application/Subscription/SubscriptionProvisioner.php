<?php

namespace App\Application\Subscription;

use App\Integration\Payload\PayloadClientInterface;

final class SubscriptionProvisioner
{
    public function __construct(private readonly PayloadClientInterface $payload)
    {
    }

    /**
     * Crée une subscription active par défaut.
     *
     * @return array<string, mixed>
     */
    public function provisionInitial(int|string $userId, string $subscriptionId, string $status = 'active'): array
    {
        return $this->payload->createSubscription($userId, $subscriptionId, $status);
    }

    /**
     * Marque une subscription comme annulée en fin de période.
     *
     * @return array<string, mixed>
     */
    public function markCanceledAtPeriodEnd(int|string $id): array
    {
        return $this->payload->updateSubscription($id, [
            'status' => 'canceled',
            'cancelAtPeriodEnd' => true,
        ]);
    }

    /**
     * Liste paginée des subscriptions.
     *
     * @return array<string, mixed>
     */
    public function list(int $page = 1, int $limit = 10): array
    {
        return $this->payload->listSubscriptions($page, $limit);
    }
}

