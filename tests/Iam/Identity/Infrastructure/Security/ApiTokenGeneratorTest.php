<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Infrastructure\Security\ApiTokenGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiTokenGeneratorTest extends TestCase
{
    #[Test]
    public function itGeneratesAnIdentifierAndSecretInTheExpectedFormat(): void
    {
        // Given
        $generator = new ApiTokenGenerator();

        // When
        $apiKey = $generator->generate();

        // Then
        self::assertMatchesRegularExpression('/^key_[0-9a-f]{16}$/', $apiKey->identifier);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $apiKey->secret);
    }

    #[Test]
    public function itGeneratesAUniqueIdentifierAndSecretEachTime(): void
    {
        // Given
        $generator = new ApiTokenGenerator();

        // When
        $first = $generator->generate();
        $second = $generator->generate();

        // Then
        self::assertNotSame($first->identifier, $second->identifier);
        self::assertNotSame($first->secret, $second->secret);
    }
}
