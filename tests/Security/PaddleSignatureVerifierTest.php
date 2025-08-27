<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\PaddleSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class PaddleSignatureVerifierTest extends TestCase
{
    private const SECRET = 'test_secret';

    private function makeVerifier(): PaddleSignatureVerifier
    {
        // Bypass Autowire attribute by providing the secret directly
        return new PaddleSignatureVerifier(self::SECRET);
    }

    public function testValidSignature(): void
    {
        $verifier = $this->makeVerifier();
        $raw = json_encode(['hello' => 'world'], JSON_THROW_ON_ERROR);
        $sig = base64_encode(hash_hmac('sha256', $raw, self::SECRET, true));

        self::assertTrue($verifier->isValid($sig, $raw));
    }

    public function testInvalidSignature(): void
    {
        $verifier = $this->makeVerifier();
        $raw = json_encode(['x' => 1], JSON_THROW_ON_ERROR);
        $sig = base64_encode(hash_hmac('sha256', $raw, 'wrong_secret', true));

        self::assertFalse($verifier->isValid($sig, $raw));
    }

    public function testEmptyOrNullSignature(): void
    {
        $verifier = $this->makeVerifier();
        $raw = '{}';

        self::assertFalse($verifier->isValid('', $raw));
        self::assertFalse($verifier->isValid(null, $raw));
    }
}
