<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineFinderInterface;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\AbstractIntegrationTestCase;

final class DbalOrderSummaryLineFinderTest extends AbstractIntegrationTestCase
{
    private OrderSummaryLineFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderSummaryLineFinderInterface::class);
    }

    #[Test]
    public function itFiltersByOrderInPositionOrder(): void
    {
        // Given
        $other = OrderBuilder::new()->withLines([
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Gizmo'), Money::fromCents(2_000)), 5),
        ])->create();
        $order = OrderBuilder::new()->withLines([
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Widget'), Money::fromCents(1_000)), 2),
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Gadget'), Money::fromCents(3_000)), 1),
        ])->create();
        $this->store($other, $order);

        // When
        $lines = iterator_to_array($this->finder->byOrder($order->id->toString()));

        // Then
        self::assertCount(2, $lines);
        self::assertSame($order->id->toString(), $lines[0]->orderId);
        self::assertSame('Widget', $lines[0]->label);
        self::assertSame(2, $lines[0]->quantity);
        self::assertSame(1_000, $lines[0]->unitAmountInCents);
        self::assertSame($order->id->toString(), $lines[1]->orderId);
        self::assertSame('Gadget', $lines[1]->label);
        self::assertSame(1, $lines[1]->quantity);
        self::assertSame(3_000, $lines[1]->unitAmountInCents);
    }
}
