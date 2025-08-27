<?php

namespace App\Integration\Payload;

interface PayloadClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function listSubscriptions(int $page = 1, int $limit = 10): array;

    /**
     * @return array<string, mixed>
     */
    public function createSubscription(int|string $userId, string $subscriptionId, string $status = 'active', string $provider = 'paddle'): array;

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function updateSubscription(int|string $id, array $fields): array;

    /**
     * @return array<string, mixed>
     */
    public function deleteSubscription(int|string $id): array;
}
