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
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(int $cents): void
    {
        // When
        $money = Money::fromCents($cents);

        // Then
        self::assertSame($cents, $money->cents);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'amount' => [1_500];
        yield 'zero' => [0];
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
    public function itEquals(): void
    {
        // Given
        $a = Money::fromCents(1_500);
        $b = Money::fromCents(1_500);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Money::fromCents(1_500);
        $b = Money::fromCents(1_499);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }

    #[Test]
    public function itAdds(): void
    {
        // When
        $sum = Money::fromCents(1_750)->plus(Money::fromCents(249));

        // Then
        self::assertSame(1_999, $sum->cents);
    }

    #[Test]
    public function itMultipliesByQuantity(): void
    {
        // When
        $product = Money::fromCents(83)->times(3);

        // Then
        self::assertSame(249, $product->cents);
    }
}
