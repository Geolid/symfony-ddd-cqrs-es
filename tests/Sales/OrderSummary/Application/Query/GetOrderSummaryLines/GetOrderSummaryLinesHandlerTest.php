<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\GetOrderSummaryLines;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Sales\OrderSummary\Application\Query\GetOrderSummaryLines\GetOrderSummaryLines;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\AbstractIntegrationTestCase;

final class GetOrderSummaryLinesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsTheLinesOfAnOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()
            ->withLines([OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Widget'), Money::fromCents(1_500)), 2)])
            ->store();

        // When
        $results = $this->ask(new GetOrderSummaryLines($order->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->orderId);
        self::assertSame('Widget', $results[0]->label);
        self::assertSame(2, $results[0]->quantity);
        self::assertSame(1_500, $results[0]->unitAmountInCents);
    }

    #[Test]
    public function itGetsNoLinesForAnUnknownOrder(): void
    {
        // When
        $results = $this->ask(new GetOrderSummaryLines(Uuid::uuid7()->toString()));

        // Then
        self::assertSame([], $results);
    }
}
