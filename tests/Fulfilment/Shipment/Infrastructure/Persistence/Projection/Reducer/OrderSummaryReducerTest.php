<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Reducer;

use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Reducer\OrderSummaryReducer;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Support\AbstractIntegrationTestCase;

final class OrderSummaryReducerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReadsTheOrderSummaryFromSalesIntegrationEventStream(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceOrder($orderId, 'customer-1', 3_500));

        // When
        $summary = $this->service(OrderSummaryReducer::class)->forOrder($orderId);

        // Then
        self::assertNotNull($summary);
        self::assertSame('customer-1', $summary->customerId);
        self::assertSame(3_500, $summary->totalAmountInCents);
    }

    #[Test]
    public function itReturnsNullForAnUnknownOrder(): void
    {
        // When
        $summary = $this->service(OrderSummaryReducer::class)->forOrder(Uuid::uuid7()->toString());

        // Then
        self::assertNull($summary);
    }
}
