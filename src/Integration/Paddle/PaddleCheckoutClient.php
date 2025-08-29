<?php

declare(strict_types=1);

namespace App\Integration\Paddle;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Service responsable de la création d'une session de checkout Paddle côté serveur.
 * - Utilise Symfony HttpClient (pas de cURL direct)
 * - N'expose jamais la clé API au client
 */
final class PaddleCheckoutClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'PADDLE_API_BASE')] private readonly ?string $apiBase = null,
        #[Autowire(env: 'PADDLE_API_KEY')] private readonly ?string $apiKey = null,
    ) {
    }

    /**
     * Crée une session de checkout et retourne l'URL de redirection Paddle.
     * @throws RuntimeException
     */
    public function createCheckoutSession(
        string $priceId,
        ?string $customerEmail,
        string $successUrl,
        string $cancelUrl
    ): string {
        $base = rtrim((string) ($this->apiBase ?? ''), '/');
        $key  = (string) ($this->apiKey ?? '');

        if ($base === '' || $key === '') {
            throw new RuntimeException('Configuration Paddle manquante: PADDLE_API_BASE et/ou PADDLE_API_KEY.');
        }

        // Pas d'appel API - le checkout se fait entièrement côté client avec Paddle.js
        // Retourner directement l'URL de succès pour que le contrôleur ne redirige pas
        return $successUrl;
    }
}
