<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

final class CheckoutItemTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $label = Label::fromString('Saucer');
        $taxRate = TaxRate::fromBasisPoints(2_000);

        // When
        $item = CheckoutItem::of($productId, $label, Money::fromCents(1_750, 'EUR'), Quantity::of(2), $taxRate);

        // Then
        self::assertSame($productId, $item->productId);
        self::assertSame('Saucer', $item->label->value);
        self::assertSame(1_750, $item->unitPrice->cents);
        self::assertSame(2, $item->quantity->value);
        self::assertSame(2_000, $item->taxRate->basisPoints);
    }

    #[Test]
    public function itProtectsInvariants(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        CheckoutItem::of('', Label::fromString('Saucer'), Money::fromCents(1_750, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $a = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));
        $b = CheckoutItem::of($productId, Label::fromString('  Saucer  '), Money::fromCents(83, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $a = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));

        $differentProductId = CheckoutItem::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));
        $differentLabel = CheckoutItem::of($productId, Label::fromString('Plate'), Money::fromCents(83, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));
        $differentPrice = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(90, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2_000));
        $differentQuantity = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83, 'EUR'), Quantity::of(3), TaxRate::fromBasisPoints(2_000));
        $differentTaxRate = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(1_900));

        // When
        $differsOnProductId = $a->equals($differentProductId);
        $differsOnLabel = $a->equals($differentLabel);
        $differsOnPrice = $a->equals($differentPrice);
        $differsOnQuantity = $a->equals($differentQuantity);
        $differsOnTaxRate = $a->equals($differentTaxRate);

        // Then
        self::assertFalse($differsOnProductId);
        self::assertFalse($differsOnLabel);
        self::assertFalse($differsOnPrice);
        self::assertFalse($differsOnQuantity);
        self::assertFalse($differsOnTaxRate);
    }

    #[Test]
    #[DataProvider('provideTotals')]
    public function itComputesTaxedTotal(int $unitPriceInCents, int $quantity, int $basisPoints, int $expectedExcludingTax, int $expectedTaxAmount, int $expectedIncludingTax): void
    {
        // Given
        $item = CheckoutItem::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents($unitPriceInCents, 'EUR'), Quantity::of($quantity), TaxRate::fromBasisPoints($basisPoints));

        // When
        $total = $item->taxedTotal();

        // Then
        self::assertSame($expectedExcludingTax, $total->excludingTax->cents);
        self::assertSame($expectedTaxAmount, $total->taxAmount->cents);
        self::assertSame($expectedIncludingTax, $total->includingTax->cents);
    }

    /**
     * @return iterable<string, array{int, int, int, int, int, int}>
     */
    public static function provideTotals(): iterable
    {
        yield 'typical' => [83, 3, 2_000, 249, 50, 299];
        yield 'rounds down' => [11, 1, 1_900, 11, 2, 13];
        yield 'rounds up' => [10, 1, 1_900, 10, 2, 12];
        yield 'discriminates a higher divisor' => [5_003, 1, 2_000, 5_003, 1_001, 6_004];
        yield 'discriminates a lower divisor' => [5_002, 1, 2_000, 5_002, 1_000, 6_002];
    }
}
