<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
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
    public function itFiltersLinesByOrderInPositionOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->withLines([
            OrderLine::of('Widget', 2, Money::fromCents(1_000)),
            OrderLine::of('Gadget', 1, Money::fromCents(3_000)),
        ])->create();
        $this->store($order);

        // When
        $lines = iterator_to_array($this->finder->withOrder($order->id()->toString()));

        // Then
        self::assertCount(2, $lines);
        self::assertSame('Widget', $lines[0]->label);
        self::assertSame(2, $lines[0]->quantity);
        self::assertSame(1_000, $lines[0]->unitAmountInCents);
        self::assertSame('Gadget', $lines[1]->label);
    }

    #[Test]
    public function itFiltersNoLinesForAnUnknownOrder(): void
    {
        // When
        $lines = iterator_to_array($this->finder->withOrder(Uuid::uuid7()->toString()));

        // Then
        self::assertSame([], $lines);
    }

    #[Test]
    public function itScopesTheLinesToTheirOwnOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->withLines([
            OrderLine::of('Widget', 2, Money::fromCents(1_000)),
        ])->create();
        $this->store($order);
        $this->store(OrderTestFactory::new()->withLines([
            OrderLine::of('Gizmo', 5, Money::fromCents(2_000)),
        ])->create());

        // When
        $lines = iterator_to_array($this->finder->withOrder($order->id()->toString()));

        // Then
        self::assertCount(1, $lines);
        self::assertSame('Widget', $lines[0]->label);
    }
}
