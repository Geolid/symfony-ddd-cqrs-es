<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Cart\Domain\ValueObject\CartLineId;

final class CartLineIdTest extends TestCase
{
    private const string CART_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = CartLineId::forCart(self::CART_ID, 0);

        // Then
        self::assertSame('aca67b07-e7da-549b-987f-2d9b217d2132', $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        CartLineId::fromString($value);
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
        $a = CartLineId::forCart(self::CART_ID, 0);
        $b = CartLineId::forCart(self::CART_ID, 0);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = CartLineId::forCart(self::CART_ID, 0);
        $b = CartLineId::forCart(self::CART_ID, 1);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
