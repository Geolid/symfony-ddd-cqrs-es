<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Transformer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\OrderSummary\Application\Enum\AppOrderSummaryStatus;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Transformer\OrderSummaryStatusTransformer;

final class OrderSummaryStatusTransformerTest extends TestCase
{
    #[Test]
    #[DataProvider('provideStatuses')]
    public function itComputesTheCompositeStatus(string $orderStatus, ?string $paymentStatus, ?string $shipmentStatus, AppOrderSummaryStatus $expected): void
    {
        // Given
        $transformer = new OrderSummaryStatusTransformer();

        // When
        $status = $transformer->compute($orderStatus, $paymentStatus, $shipmentStatus);

        // Then
        self::assertSame($expected, $status);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string, AppOrderSummaryStatus}>
     */
    public static function provideStatuses(): iterable
    {
        yield 'cancelled order, regardless of payment/shipment' => ['cancelled', 'captured', 'dispatched', AppOrderSummaryStatus::CANCELLED];
        yield 'placed, no payment yet' => ['placed', null, null, AppOrderSummaryStatus::PLACED];
        yield 'payment requested' => ['placed', 'requested', null, AppOrderSummaryStatus::PAYMENT_PENDING];
        yield 'captured, no shipment yet' => ['placed', 'captured', null, AppOrderSummaryStatus::PREPARING];
        yield 'captured, shipment pending' => ['placed', 'captured', 'pending', AppOrderSummaryStatus::PREPARING];
        yield 'captured, shipment dispatched' => ['placed', 'captured', 'dispatched', AppOrderSummaryStatus::DISPATCHED];
        yield 'captured, shipment delivered' => ['placed', 'captured', 'delivered', AppOrderSummaryStatus::DELIVERED];
    }
}
