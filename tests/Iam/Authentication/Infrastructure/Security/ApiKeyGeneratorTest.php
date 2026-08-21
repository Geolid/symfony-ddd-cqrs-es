<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Security;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Authentication\Infrastructure\Security\ApiKeyGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiKeyGeneratorTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // Given
        $generator = new ApiKeyGenerator();

        // When
        $apiKey = $generator->generate();

        // Then
        KeyId::fromString($apiKey->keyId);
        self::assertNotSame('', $apiKey->secret);
    }

    #[Test]
    public function itGeneratesUniquely(): void
    {
        // Given
        $generator = new ApiKeyGenerator();

        // When
        $a = $generator->generate();
        $b = $generator->generate();

        // Then
        self::assertNotSame($a->keyId, $b->keyId);
        self::assertNotSame($a->secret, $b->secret);
    }
}
