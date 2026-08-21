<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Security;

use Iam\Authentication\Infrastructure\Security\ApiKeyHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiKeyHasherTest extends TestCase
{
    #[Test]
    public function itHashesDeterministically(): void
    {
        // Given
        $hasher = new ApiKeyHasher();

        // When / Then
        self::assertSame($hasher->hash('a-secret-value'), $hasher->hash('a-secret-value'));
    }

    #[Test]
    public function itHashesAndVerifies(): void
    {
        // Given
        $hasher = new ApiKeyHasher();

        // When
        $hash = $hasher->hash('a-secret-value');

        // Then
        self::assertTrue($hasher->verify($hash, 'a-secret-value'));
        self::assertFalse($hasher->verify($hash, 'another-secret-value'));
    }
}
