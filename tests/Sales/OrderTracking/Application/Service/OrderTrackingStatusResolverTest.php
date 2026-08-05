<?php

declare(strict_types=1);

namespace Sales\Tests\OrderTracking\Application\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\OrderTracking\Application\Service\OrderTrackingStatusResolver;

final class OrderTrackingStatusResolverTest extends TestCase
{
    #[Test]
    #[DataProvider('provideOrderPaymentAndShipmentStates')]
    public function itResolvesTheUnifiedStatus(
        string $orderStatus,
        ?string $paymentStatus,
        ?string $shipmentStatus,
        string $expected,
    ): void {
        // When
        $status = new OrderTrackingStatusResolver()->resolve($orderStatus, $paymentStatus, $shipmentStatus);

        // Then
        self::assertSame($expected, $status);
    }

    /**
     * @return iterable<string, array{string, ?string, ?string, string}>
     */
    public static function provideOrderPaymentAndShipmentStates(): iterable
    {
        yield 'cancelled order takes priority over an already captured payment and dispatched shipment' => [
            'cancelled', 'captured', 'dispatched', 'cancelled',
        ];
        yield 'placed order with no payment yet' => ['placed', null, null, 'placed'];
        yield 'payment requested but not yet captured' => ['placed', 'requested', null, 'payment_pending'];
        yield 'payment captured, shipment not created yet' => ['placed', 'captured', null, 'preparing'];
        yield 'payment captured, shipment still pending' => ['placed', 'captured', 'pending', 'preparing'];
        yield 'shipment dispatched' => ['placed', 'captured', 'dispatched', 'dispatched'];
        yield 'shipment delivered' => ['placed', 'captured', 'delivered', 'delivered'];
    }
}
