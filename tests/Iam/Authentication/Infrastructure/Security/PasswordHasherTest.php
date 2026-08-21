<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Security;

use Iam\Authentication\Infrastructure\Security\PasswordHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class PasswordHasherTest extends TestCase
{
    #[Test]
    public function itHashesAndVerifies(): void
    {
        // Given
        $hasher = new PasswordHasher(new NativePasswordHasher());

        // When
        $hash = $hasher->hash('Xk9$mQ2vLp7&zR4w');

        // Then
        self::assertTrue($hasher->verify($hash, 'Xk9$mQ2vLp7&zR4w'));
        self::assertFalse($hasher->verify($hash, 'WrongHorse456!'));
    }

    #[Test]
    public function itRequiresRehash(): void
    {
        // Given
        $hasher = new PasswordHasher(new NativePasswordHasher(cost: 4));
        $strongerHasher = new PasswordHasher(new NativePasswordHasher(cost: 12));

        // When
        $hash = $hasher->hash('Xk9$mQ2vLp7&zR4w');

        // Then
        self::assertTrue($strongerHasher->needsRehash($hash));
    }
}
