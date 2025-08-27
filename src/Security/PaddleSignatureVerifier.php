<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PaddleSignatureVerifier
{
    public function __construct(
        #[Autowire('%env(string:PADDLE_WEBHOOK_SECRET)%')] private readonly string $signingSecret,
    ) {
    }

    /**
     * Validate Paddle HMAC signature (Billing - shared secret).
     * Signature is expected to be base64-encoded HMAC-SHA256 of the raw request body.
     */
    public function isValid(?string $providedSignature, string $rawBody): bool
    {
        if ($providedSignature === null || $providedSignature === '') {
            return false;
        }

        $computed = base64_encode(hash_hmac('sha256', $rawBody, $this->signingSecret, true));

        // Timing-safe comparison
        return hash_equals($computed, $providedSignature);
    }
}
