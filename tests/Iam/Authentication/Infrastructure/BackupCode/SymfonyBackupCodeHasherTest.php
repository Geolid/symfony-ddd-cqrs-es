<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\BackupCode;

use Iam\Authentication\Infrastructure\BackupCode\SymfonyBackupCodeHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class SymfonyBackupCodeHasherTest extends TestCase
{
    private SymfonyBackupCodeHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new SymfonyBackupCodeHasher(new NativePasswordHasher(cost: 4));
    }

    #[Test]
    public function itVerifies(): void
    {
        // Given
        $hashedCode = $this->hasher->hash('12345678');

        // When
        $verified = $this->hasher->verify('12345678', $hashedCode);

        // Then
        self::assertNotSame('12345678', $hashedCode);
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $hashedCode = $this->hasher->hash('12345678');

        // When
        $verified = $this->hasher->verify('87654321', $hashedCode);

        // Then
        self::assertFalse($verified);
    }
}
