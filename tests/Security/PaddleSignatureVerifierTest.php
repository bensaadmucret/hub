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
        $ts = time();
        $h1 = hash_hmac('sha256', $ts . ':' . $raw, self::SECRET);
        $header = sprintf('ts=%d;h1=%s', $ts, $h1);

        self::assertTrue($verifier->isValid($header, $raw));
    }

    public function testInvalidSignature(): void
    {
        $verifier = $this->makeVerifier();
        $raw = json_encode(['x' => 1], JSON_THROW_ON_ERROR);
        $ts = time();
        $wrong = hash_hmac('sha256', $ts . ':' . $raw, 'wrong_secret');
        $header = sprintf('ts=%d;h1=%s', $ts, $wrong);

        self::assertFalse($verifier->isValid($header, $raw));
    }

    public function testEmptyOrNullSignature(): void
    {
        $verifier = $this->makeVerifier();
        $raw = '{}';

        self::assertFalse($verifier->isValid('', $raw));
        self::assertFalse($verifier->isValid(null, $raw));
    }
}
