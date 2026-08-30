<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Password;

use Iam\Authentication\Infrastructure\Password\SymfonyPasswordHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class SymfonyPasswordHasherTest extends TestCase
{
    #[Test]
    public function itHashesAndVerifies(): void
    {
        // Given
        $hasher = new SymfonyPasswordHasher(new NativePasswordHasher());

        // When
        $hash = $hasher->hash('Marmoset-42-Zephyr!');

        // Then
        self::assertTrue($hasher->verify($hash, 'Marmoset-42-Zephyr!'));
        self::assertFalse($hasher->verify($hash, 'WrongHorse456!'));
    }

    #[Test]
    public function itRequiresRehash(): void
    {
        // Given
        $hasher = new SymfonyPasswordHasher(new NativePasswordHasher(cost: 4));
        $strongerHasher = new SymfonyPasswordHasher(new NativePasswordHasher(cost: 12));

        // When
        $hash = $hasher->hash('Marmoset-42-Zephyr!');

        // Then
        self::assertTrue($strongerHasher->needsRehash($hash));
    }
}
