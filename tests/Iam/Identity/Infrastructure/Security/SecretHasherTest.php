<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Infrastructure\Security\SecretHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class SecretHasherTest extends TestCase
{
    #[Test]
    public function itVerifiesAMatchingSecret(): void
    {
        // Given
        $hasher = new SecretHasher(new NativePasswordHasher(cost: 4));
        $hash = $hasher->hash('S3cr3t!');

        // When
        $verified = $hasher->verify($hash, 'S3cr3t!');

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRejectsAWrongSecret(): void
    {
        // Given
        $hasher = new SecretHasher(new NativePasswordHasher(cost: 4));
        $hash = $hasher->hash('S3cr3t!');

        // When
        $verified = $hasher->verify($hash, 'wrong');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itFindsNoRehashNeededWhenTheCostMatches(): void
    {
        // Given
        $hasher = new SecretHasher(new NativePasswordHasher(cost: 4));
        $hash = $hasher->hash('S3cr3t!');

        // When
        $needsRehash = $hasher->needsRehash($hash);

        // Then
        self::assertFalse($needsRehash);
    }

    #[Test]
    public function itFindsARehashNeededWhenTheCostChanged(): void
    {
        // Given
        $staleHash = new SecretHasher(new NativePasswordHasher(cost: 4))->hash('S3cr3t!');
        $hasher = new SecretHasher(new NativePasswordHasher(cost: 5));

        // When
        $needsRehash = $hasher->needsRehash($staleHash);

        // Then
        self::assertTrue($needsRehash);
    }
}
