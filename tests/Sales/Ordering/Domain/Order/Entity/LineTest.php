<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Entity\Line;
use Sales\Ordering\Domain\Order\ValueObject\LineId;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Sales\Ordering\Domain\Order\ValueObject\Quantity;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class LineTest extends TestCase
{
    #[Test]
    public function itComputesTotal(): void
    {
        // Given
        $line = new Line(
            LineId::forOrder(Uuid::uuid7()->toString(), 0),
            Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83, 'EUR')),
            Quantity::of(3),
            Money::fromCents(50, 'EUR'),
        );

        // When
        $total = $line->total();

        // Then
        self::assertSame(249, $total->cents);
    }

    #[Test]
    public function itComputesTaxedTotal(): void
    {
        // Given
        $line = new Line(
            LineId::forOrder(Uuid::uuid7()->toString(), 0),
            Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83, 'EUR')),
            Quantity::of(3),
            Money::fromCents(50, 'EUR'),
        );

        // When
        $taxedTotal = $line->taxedTotal();

        // Then
        self::assertSame(249, $taxedTotal->excludingTax->cents);
        self::assertSame(50, $taxedTotal->taxAmount->cents);
        self::assertSame(299, $taxedTotal->includingTax->cents);
    }
}
