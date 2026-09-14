<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\TaxedAmount;

final class TaxedAmountTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $excludingTax = Money::fromCents(1_000, 'EUR');
        $taxAmount = Money::fromCents(200, 'EUR');

        // When
        $taxedAmount = TaxedAmount::of($excludingTax, $taxAmount);

        // Then
        self::assertSame($excludingTax, $taxedAmount->excludingTax);
        self::assertSame($taxAmount, $taxedAmount->taxAmount);
        self::assertSame(1_200, $taxedAmount->includingTax->cents);
    }

    #[Test]
    public function itCreatesZero(): void
    {
        // When
        $taxedAmount = TaxedAmount::zero(Currency::EUR);

        // Then
        self::assertSame(0, $taxedAmount->excludingTax->cents);
        self::assertSame(0, $taxedAmount->taxAmount->cents);
        self::assertSame(0, $taxedAmount->includingTax->cents);
    }

    #[Test]
    public function itAdds(): void
    {
        // Given
        $a = TaxedAmount::of(Money::fromCents(1_000, 'EUR'), Money::fromCents(200, 'EUR'));
        $b = TaxedAmount::of(Money::fromCents(500, 'EUR'), Money::fromCents(100, 'EUR'));

        // When
        $sum = $a->plus($b);

        // Then
        self::assertSame(1_500, $sum->excludingTax->cents);
        self::assertSame(300, $sum->taxAmount->cents);
        self::assertSame(1_800, $sum->includingTax->cents);
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = TaxedAmount::of(Money::fromCents(1_000, 'EUR'), Money::fromCents(200, 'EUR'));
        $b = TaxedAmount::of(Money::fromCents(1_000, 'EUR'), Money::fromCents(200, 'EUR'));

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = TaxedAmount::of(Money::fromCents(1_000, 'EUR'), Money::fromCents(200, 'EUR'));
        $differentExcludingTax = TaxedAmount::of(Money::fromCents(999, 'EUR'), Money::fromCents(200, 'EUR'));
        $differentTaxAmount = TaxedAmount::of(Money::fromCents(1_000, 'EUR'), Money::fromCents(199, 'EUR'));

        // When
        $differsOnExcludingTax = $a->equals($differentExcludingTax);
        $differsOnTaxAmount = $a->equals($differentTaxAmount);

        // Then
        self::assertFalse($differsOnExcludingTax);
        self::assertFalse($differsOnTaxAmount);
    }
}
