<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\IdentityId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class IdentityIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = IdentityId::fromString(Uuid::uuid7()->toString());

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
        IdentityId::fromString($value);
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
    public function itEquals(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $a = IdentityId::fromString($value);
        $b = IdentityId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = IdentityId::fromString(Uuid::uuid7()->toString());
        $b = IdentityId::fromString(Uuid::uuid7()->toString());

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
