<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Projection\Transformer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\OrderSummary\Application\Status\OrderSummaryStatus;
use Sales\OrderSummary\Infrastructure\Projection\Transformer\OrderSummaryStatusTransformer;

final class OrderSummaryStatusTransformerTest extends TestCase
{
    #[Test]
    #[DataProvider('provideStatuses')]
    public function itComputesCompositeStatus(string $orderStatus, ?string $paymentStatus, ?string $shipmentStatus, OrderSummaryStatus $expected): void
    {
        // Given
        $transformer = new OrderSummaryStatusTransformer();

        // When
        $status = $transformer->compute($orderStatus, $paymentStatus, $shipmentStatus);

        // Then
        self::assertSame($expected, $status);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string, OrderSummaryStatus}>
     */
    public static function provideStatuses(): iterable
    {
        yield 'cancelled order, regardless of payment/shipment' => ['cancelled', 'captured', 'dispatched', OrderSummaryStatus::CANCELLED];
        yield 'placed, no payment yet' => ['placed', null, null, OrderSummaryStatus::PLACED];
        yield 'payment requested' => ['placed', 'requested', null, OrderSummaryStatus::PAYMENT_PENDING];
        yield 'captured, no shipment yet' => ['placed', 'captured', null, OrderSummaryStatus::PREPARING];
        yield 'captured, shipment manifested' => ['placed', 'captured', 'manifested', OrderSummaryStatus::PREPARING];
        yield 'captured, shipment dispatched' => ['placed', 'captured', 'dispatched', OrderSummaryStatus::DISPATCHED];
        yield 'captured, shipment delivered' => ['placed', 'captured', 'delivered', OrderSummaryStatus::DELIVERED];
    }
}
