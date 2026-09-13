<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

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
    public function itComputesTotal(): void
    {
        // Given
        $item = CheckoutItem::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83, 'EUR'), Quantity::of(3), TaxRate::fromBasisPoints(2_000));

        // When
        $total = $item->total();

        // Then
        self::assertSame(249, $total->excludingTax->cents);
        self::assertSame(50, $total->taxAmount->cents);
        self::assertSame(299, $total->includingTax->cents);
    }
}
