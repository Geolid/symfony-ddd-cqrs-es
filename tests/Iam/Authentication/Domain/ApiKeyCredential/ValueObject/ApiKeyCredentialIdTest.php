<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\ApiKeyCredential\ValueObject;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ApiKeyCredentialIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ApiKeyCredentialId::generate();

        // Then
        self::assertTrue(Uuid::isValid($id->toString()));
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        ApiKeyCredentialId::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid uuid' => ['not-a-uuid'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $value = ApiKeyCredentialId::generate()->toString();

        // When
        $a = ApiKeyCredentialId::fromString($value);
        $b = ApiKeyCredentialId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(ApiKeyCredentialId::generate()));
    }
}
