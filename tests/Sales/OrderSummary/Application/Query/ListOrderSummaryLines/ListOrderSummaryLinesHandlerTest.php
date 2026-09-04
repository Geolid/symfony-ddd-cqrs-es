<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\ListOrderSummaryLines;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Sales\OrderSummary\Application\Query\ListOrderSummaryLines\ListOrderSummaryLines;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\TestCase\AbstractIntegrationTestCase;

final class ListOrderSummaryLinesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $order = OrderBuilder::new()
            ->withLines([OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Widget'), Money::fromCents(1_500)), 2)])
            ->create();
        $this->store($order);

        // When
        $results = $this->ask(new ListOrderSummaryLines($order->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->orderId);
        self::assertSame('Widget', $results[0]->label);
        self::assertSame(2, $results[0]->quantity);
        self::assertSame(1_500, $results[0]->unitPriceInCents);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = $this->ask(new ListOrderSummaryLines(Uuid::uuid7()->toString()));

        // Then
        self::assertSame([], $results);
    }
}
