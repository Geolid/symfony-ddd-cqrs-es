<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Domain\Cart\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Cart\Domain\ValueObject\CartId;

final class CartIdTest extends TestCase
{
    private const string KNOWN_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itCreatesFromString(): void
    {
        // When
        $id = CartId::fromString(self::KNOWN_ID);

        // Then
        self::assertSame(self::KNOWN_ID, $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        CartId::fromString($value);
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
        $a = CartId::fromString(self::KNOWN_ID);
        $b = CartId::fromString(self::KNOWN_ID);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = CartId::fromString(self::KNOWN_ID);
        $b = CartId::fromString('0199a1b2-3c4d-7e5f-8061-72839405a6b8');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
