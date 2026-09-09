<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Shared\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;

final class QuantityTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $quantity = Quantity::of(3);

        // Then
        self::assertSame(3, $quantity->value);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(int $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Quantity::of($value);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    #[Test]
    public function itAdds(): void
    {
        // Given
        $a = Quantity::of(2);
        $b = Quantity::of(3);

        // When
        $sum = $a->plus($b);

        // Then
        self::assertSame(5, $sum->value);
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = Quantity::of(3);
        $b = Quantity::of(3);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Quantity::of(3);
        $b = Quantity::of(4);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
