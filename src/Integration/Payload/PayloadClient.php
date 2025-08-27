<?php

namespace App\Integration\Payload;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PayloadClient implements PayloadClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    /**
     * @param array<string, string> $extra
     * @return array<string, string>
     */
    private function headers(array $extra = []): array
    {
        return array_merge([
            'x-payload-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $extra);
    }

    /**
     * GET /api/subscriptions
     */
    public function listSubscriptions(int $page = 1, int $limit = 10): array
    {
        $url = sprintf('%s/api/subscriptions?page=%d&limit=%d', rtrim($this->baseUrl, '/'), $page, $limit);

        $response = $this->http->request('GET', $url, [
            'headers' => $this->headers(),
            'timeout' => 10,
        ]);

        return $response->toArray(false);
    }

    /**
     * POST /api/subscriptions
     */
    public function createSubscription(int|string $userId, string $subscriptionId, string $status = 'active', string $provider = 'paddle'): array
    {
        $url = sprintf('%s/api/subscriptions', rtrim($this->baseUrl, '/'));
        $payload = [
            'user' => $userId,
            'provider' => $provider,
            'subscriptionId' => $subscriptionId,
            'status' => $status, // trialing|active|past_due|canceled
        ];

        $response = $this->http->request('POST', $url, [
            'headers' => $this->headers(),
            'json' => $payload,
            'timeout' => 10,
        ]);

        return $response->toArray(false);
    }

    /**
     * PATCH /api/subscriptions/{id}
     */
    public function updateSubscription(int|string $id, array $fields): array
    {
        $url = sprintf('%s/api/subscriptions/%s', rtrim($this->baseUrl, '/'), $id);

        $response = $this->http->request('PATCH', $url, [
            'headers' => $this->headers(),
            'json' => $fields,
            'timeout' => 10,
        ]);

        return $response->toArray(false);
    }

    /**
     * DELETE /api/subscriptions/{id}
     */
    public function deleteSubscription(int|string $id): array
    {
        $url = sprintf('%s/api/subscriptions/%s', rtrim($this->baseUrl, '/'), $id);

        $response = $this->http->request('DELETE', $url, [
            'headers' => $this->headers(),
            'timeout' => 10,
        ]);

        return $response->toArray(false);
    }
}
