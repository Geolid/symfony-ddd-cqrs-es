<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Money;

final class MoneyTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $money = Money::fromCents(1_500);

        // Then
        self::assertSame(1_500, $money->toCents());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(int $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Money::fromCents($value);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'negative amount' => [-1];
        yield 'largely negative amount' => [\PHP_INT_MIN];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = Money::fromCents(1_500);
        $b = Money::fromCents(1_500);
        $other = Money::fromCents(1_501);

        // When
        $equalResult = $a->equals($b);
        $differentResult = $a->equals($other);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentResult);
    }

    #[Test]
    public function itAddsAnotherAmount(): void
    {
        // When
        $sum = Money::fromCents(1_750)->plus(Money::fromCents(249));

        // Then
        self::assertSame(1_999, $sum->toCents());
    }

    #[Test]
    public function itMultipliesByAQuantity(): void
    {
        // When
        $product = Money::fromCents(83)->times(3);

        // Then
        self::assertSame(249, $product->toCents());
    }
}
