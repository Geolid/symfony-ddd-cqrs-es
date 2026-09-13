<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\Quantity;

final class CheckoutItemTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $label = Label::fromString('Saucer');

        // When
        $item = CheckoutItem::of($productId, $label, Money::fromCents(1_750), Quantity::of(2));

        // Then
        self::assertSame($productId, $item->productId);
        self::assertSame('Saucer', $item->label->value);
        self::assertSame(1_750, $item->unitPrice->cents);
        self::assertSame(2, $item->quantity->value);
    }

    #[Test]
    public function itProtectsInvariants(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        CheckoutItem::of('', Label::fromString('Saucer'), Money::fromCents(1_750), Quantity::of(2));
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $a = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83), Quantity::of(2));
        $b = CheckoutItem::of($productId, Label::fromString('  Saucer  '), Money::fromCents(83), Quantity::of(2));

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
        $a = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83), Quantity::of(2));

        $differentProductId = CheckoutItem::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83), Quantity::of(2));
        $differentLabel = CheckoutItem::of($productId, Label::fromString('Plate'), Money::fromCents(83), Quantity::of(2));
        $differentPrice = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(90), Quantity::of(2));
        $differentQuantity = CheckoutItem::of($productId, Label::fromString('Saucer'), Money::fromCents(83), Quantity::of(3));

        // When
        $differsOnProductId = $a->equals($differentProductId);
        $differsOnLabel = $a->equals($differentLabel);
        $differsOnPrice = $a->equals($differentPrice);
        $differsOnQuantity = $a->equals($differentQuantity);

        // Then
        self::assertFalse($differsOnProductId);
        self::assertFalse($differsOnLabel);
        self::assertFalse($differsOnPrice);
        self::assertFalse($differsOnQuantity);
    }

    #[Test]
    public function itComputesSubtotal(): void
    {
        // Given
        $item = CheckoutItem::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83), Quantity::of(3));

        // When
        $subtotal = $item->subtotal();

        // Then
        self::assertSame(249, $subtotal->cents);
    }
}
