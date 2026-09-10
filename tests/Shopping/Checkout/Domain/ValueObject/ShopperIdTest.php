<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

final class ShopperIdTest extends TestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = ShopperId::fromString(Uuid::uuid7()->toString());

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
        ShopperId::fromString($value);
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
        $a = ShopperId::fromString($value);
        $b = ShopperId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = ShopperId::fromString(Uuid::uuid7()->toString());
        $b = ShopperId::fromString(Uuid::uuid7()->toString());

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
