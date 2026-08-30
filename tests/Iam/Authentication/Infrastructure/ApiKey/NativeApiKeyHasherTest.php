<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\ApiKey;

use Iam\Authentication\Infrastructure\ApiKey\NativeApiKeyHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeApiKeyHasherTest extends TestCase
{
    #[Test]
    public function itHashesDeterministically(): void
    {
        // Given
        $hasher = new NativeApiKeyHasher();

        // When
        $hash = $hasher->hash('a-secret-value');

        // Then
        self::assertSame($hash, $hasher->hash('a-secret-value'));
    }

    #[Test]
    public function itHashesAndVerifies(): void
    {
        // Given
        $hasher = new NativeApiKeyHasher();

        // When
        $hash = $hasher->hash('a-secret-value');

        // Then
        self::assertTrue($hasher->verify($hash, 'a-secret-value'));
        self::assertFalse($hasher->verify($hash, 'another-secret-value'));
    }
}
