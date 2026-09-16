<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Totp;

use Iam\Authentication\Infrastructure\Totp\NativeTotpCipher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeTotpCipherTest extends TestCase
{
    private NativeTotpCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = new NativeTotpCipher('a-dedicated-encryption-secret');
    }

    #[Test]
    public function itRoundTrips(): void
    {
        // When
        $encrypted = $this->cipher->encrypt('GEZDGNBVGY3TQOJQ');
        $decrypted = $this->cipher->decrypt($encrypted);

        // Then
        self::assertNotSame('GEZDGNBVGY3TQOJQ', $encrypted);
        self::assertSame('GEZDGNBVGY3TQOJQ', $decrypted);
    }

    #[Test]
    public function itCannotDecryptWithAnotherKey(): void
    {
        // Given
        $encrypted = $this->cipher->encrypt('GEZDGNBVGY3TQOJQ');
        $otherCipher = new NativeTotpCipher('another-encryption-secret');

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $otherCipher->decrypt($encrypted);
    }
}
