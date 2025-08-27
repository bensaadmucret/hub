<?php

namespace App\Tests\Integration\Payload;

use App\Integration\Payload\PayloadClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PayloadClientTest extends TestCase
{
    public function testListAddsApiKeyHeader(): void
    {
        $responses = [
            new MockResponse(json_encode(['docs' => []]), [
                'response_headers' => ['Content-Type' => 'application/json'],
            ]),
        ];
        /** @var array<string,mixed>|null $capturedOptions */
        $capturedOptions = null;
        $mock = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions, $responses) {
            $capturedOptions = $options;
            return $responses[0];
        });

        $client = new PayloadClient($mock, 'http://localhost:3000', 'TESTKEY');
        $client->listSubscriptions();

        self::assertIsArray($capturedOptions, 'HTTP client callback did not capture options');
        $opts = $capturedOptions ?? [];
        self::assertArrayHasKey('headers', $opts);
        $headers = $opts['headers'];
        if (is_array($headers) && array_is_list($headers)) {
            $found = false;
            foreach ($headers as $h) {
                if (is_string($h) && stripos($h, 'x-payload-api-key:') === 0 && str_contains($h, 'TESTKEY')) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, 'x-payload-api-key header not found in normalized header list');
        } else {
            self::assertSame('TESTKEY', $headers['x-payload-api-key'] ?? null);
        }
    }
}
