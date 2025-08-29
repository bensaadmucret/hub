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
     * Validate Paddle Billing webhook signature using the ts/h1 scheme.
     * Header format: "ts=<unixTimestamp>;h1=<hex_hmac_sha256(ts:rawBody)>".
     * Allows a maximum clock skew of 300 seconds (5 minutes).
     */
    public function isValid(?string $signatureHeader, string $rawBody): bool
    {
        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(';', $signatureHeader) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($k !== null && $v !== null) {
                $parts[$k] = $v;
            }
        }

        if (!isset($parts['ts'], $parts['h1'])) {
            return false;
        }

        $ts = (int) $parts['ts'];
        $h1 = $parts['h1'];

        // Reject if timestamp is too old/new (>5 min skew)
        if (abs(time() - $ts) > 300) {
            return false;
        }

        $signedPayload = $ts . ':' . $rawBody;
        $expected = hash_hmac('sha256', $signedPayload, $this->signingSecret);

        return hash_equals($expected, $h1);
    }
}
