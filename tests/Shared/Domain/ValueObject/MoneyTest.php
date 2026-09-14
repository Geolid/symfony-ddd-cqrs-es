<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;

final class MoneyTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(int $cents): void
    {
        // When
        $money = Money::fromCents($cents, 'EUR');

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
        Money::fromCents($value, 'EUR');
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
    public function itProtectsInvariantsWhenCurrencyIsInvalid(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Money::fromCents(1_500, 'XXX');
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = Money::fromCents(1_500, 'EUR');
        $b = Money::fromCents(1_500, 'EUR');

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = Money::fromCents(1_500, 'EUR');

        $differentAmount = Money::fromCents(1_499, 'EUR');
        $differentCurrency = Money::fromCents(1_500, 'GBP');

        // When
        $differsOnAmount = $a->equals($differentAmount);
        $differsOnCurrency = $a->equals($differentCurrency);

        // Then
        self::assertFalse($differsOnAmount);
        self::assertFalse($differsOnCurrency);
    }

    #[Test]
    public function itAdds(): void
    {
        // When
        $sum = Money::fromCents(1_750, 'EUR')->plus(Money::fromCents(249, 'EUR'));

        // Then
        self::assertSame(1_999, $sum->cents);
    }

    #[Test]
    public function itProtectsInvariantsWhenAddingDifferentCurrencies(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Money::fromCents(1_750, 'EUR')->plus(Money::fromCents(249, 'GBP'));
    }

    #[Test]
    public function itMultipliesByQuantity(): void
    {
        // When
        $product = Money::fromCents(83, 'EUR')->times(Quantity::of(3));

        // Then
        self::assertSame(249, $product->cents);
    }
}
