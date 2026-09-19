<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Totp;

use Iam\Authentication\Infrastructure\Totp\NativeTotpCipher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeTotpCipherTest extends TestCase
{
    private const string SECRET = 'GEZDGNBVGY3TQOJQ';

    private NativeTotpCipher $cipher;

    protected function setUp(): void
    {
        $this->cipher = new NativeTotpCipher('fake-encryption-secret');
    }

    #[Test]
    public function itRoundTrips(): void
    {
        // When
        $encrypted = $this->cipher->encrypt(self::SECRET);
        $decrypted = $this->cipher->decrypt($encrypted);

        // Then
        self::assertNotSame(self::SECRET, $encrypted);
        self::assertSame(self::SECRET, $decrypted);
    }

    #[Test]
    public function itCannotDecryptWithAnotherKey(): void
    {
        // Given
        $encrypted = $this->cipher->encrypt(self::SECRET);
        $otherCipher = new NativeTotpCipher('another-fake-encryption-secret');

        // Then
        $this->expectException(\RuntimeException::class);

        // When
        $otherCipher->decrypt($encrypted);
    }
}
