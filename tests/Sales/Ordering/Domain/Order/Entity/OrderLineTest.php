<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order\Entity;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Entity\OrderLine;
use Sales\Ordering\Domain\Order\ValueObject\OrderLineId;
use Sales\Tests\Ordering\Support\Factory\OrderItemFactory;
use Shared\Domain\ValueObject\Quantity;

final class OrderLineTest extends TestCase
{
    #[Test]
    public function itComputesTotal(): void
    {
        // Given
        $item = OrderItemFactory::new()
            ->withQuantity(Quantity::of(3))
            ->create();
        $line = new OrderLine(OrderLineId::forOrder(Uuid::uuid7()->toString(), 0), $item);

        // When
        $total = $line->total();

        // Then
        self::assertSame($item->total()->cents, $total->cents);
    }

    #[Test]
    public function itComputesTaxedTotal(): void
    {
        // Given
        $item = OrderItemFactory::new()
            ->withQuantity(Quantity::of(3))
            ->create();
        $line = new OrderLine(OrderLineId::forOrder(Uuid::uuid7()->toString(), 0), $item);

        // When
        $taxedTotal = $line->taxedTotal();

        // Then
        self::assertSame($item->taxedTotal()->excludingTax->cents, $taxedTotal->excludingTax->cents);
        self::assertSame($item->taxedTotal()->taxAmount->cents, $taxedTotal->taxAmount->cents);
        self::assertSame($item->taxedTotal()->includingTax->cents, $taxedTotal->includingTax->cents);
    }
}
