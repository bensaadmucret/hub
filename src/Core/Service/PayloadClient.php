<?php

namespace App\Core\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PayloadClient
{
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $apiKey;

    public function __construct(
        string $payloadApiUrl,
        string $payloadApiKey,
        ?HttpClientInterface $client = null
    ) {
        $this->apiUrl = rtrim($payloadApiUrl, '/');
        $this->apiKey = $payloadApiKey;
        $this->client = $client ?? HttpClient::create();
    }

    /**
     * Effectue une requête vers l'API Payload
     *
     * @param string $method Méthode HTTP (GET, POST, PATCH, DELETE)
     * @param string $endpoint Endpoint de l'API (ex: 'users', 'posts/123')
     * @param array<string, mixed> $data Données à envoyer (pour POST/PATCH)
     * @param string|null $jwt JWT pour l'authentification
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function request(string $method, string $endpoint, array $data = [], ?string $jwt = null): array
    {
        $url = sprintf('%s/api/%s', $this->apiUrl, ltrim($endpoint, '/'));

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Utiliser le JWT s'il est fourni, sinon utiliser l'API Key en entête dédié
        if ($jwt) {
            $headers['Authorization'] = $jwt;
        } else {
            // Alignement avec l'intégration S2S Payload: clé transmise via x-payload-api-key
            $headers['x-payload-api-key'] = $this->apiKey;
        }

        $options = (new HttpOptions())
            ->setHeaders($headers)
            ->setMaxRedirects(0);

        if (in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $options->setJson($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $options->setQuery($data);
        }

        try {
            $response = $this->client->request($method, $url, $options->toArray());

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode >= 200 && $statusCode < 300) {
                if (empty($content)) {
                    return [];
                }
                $decoded = json_decode($content, true);
                return is_array($decoded) ? $decoded : [];
            }

            $errorData = [
                'status' => $statusCode,
                'message' => 'An error occurred',
            ];

            if ($content) {
                $decodedContent = json_decode($content, true);
                if (is_array($decodedContent)) {
                    $message = $decodedContent['message'] ?? $errorData['message'];
                    if (is_scalar($message)) {
                        $errorData['message'] = (string) $message;
                    }
                    $errorData['details'] = $decodedContent['errors'] ?? null;
                }
            }

            throw new \RuntimeException(
                $errorData['message'],
                $statusCode
            );
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $content = $e->getResponse()->getContent(false);
            $message = 'An error occurred';

            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['message'])) {
                    $decodedMessage = $decoded['message'];
                    if (is_scalar($decodedMessage)) {
                        $message = (string) $decodedMessage;
                    }
                }
            }

            throw new \RuntimeException($message, $statusCode, $e);
        } catch (RedirectionExceptionInterface | TransportExceptionInterface $e) {
            throw new \RuntimeException('Network error: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unexpected error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Raccourci pour les requêtes GET
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = [], ?string $jwt = null): array
    {
        return $this->request('GET', $endpoint, $query, $jwt);
    }

    /**
     * Raccourci pour les requêtes POST
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data = [], ?string $jwt = null): array
    {
        return $this->request('POST', $endpoint, $data, $jwt);
    }

    /**
     * Raccourci pour les requêtes PATCH
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function patch(string $endpoint, array $data = [], ?string $jwt = null): array
    {
        return $this->request('PATCH', $endpoint, $data, $jwt);
    }

    /**
     * Raccourci pour les requêtes DELETE
     * @return array<string, mixed>
     */
    public function delete(string $endpoint, ?string $jwt = null): array
    {
        return $this->request('DELETE', $endpoint, [], $jwt);
    }
}
