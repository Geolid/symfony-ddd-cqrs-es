<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ApiTokenCredentialIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ApiTokenCredentialId::generate();

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
        ApiTokenCredentialId::fromString($value);
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
        $value = ApiTokenCredentialId::generate()->toString();

        // When
        $a = ApiTokenCredentialId::fromString($value);
        $b = ApiTokenCredentialId::fromString($value);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(ApiTokenCredentialId::generate()));
    }
}
