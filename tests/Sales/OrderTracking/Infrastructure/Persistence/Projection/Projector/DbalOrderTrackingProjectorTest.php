<?php

declare(strict_types=1);

namespace Sales\Tests\OrderTracking\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\OrderTracking\Infrastructure\Persistence\Projection\Projector\DbalOrderTrackingProjector;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string, order_status: string, payment_status: ?string, shipment_status: ?string, status: string, placed_at: string}
 */
final class DbalOrderTrackingProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsThePlacementOnOrderPlaced(): void
    {
        // When
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->create();
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('customer-1', $row['customer_id']);
        self::assertSame('placed', $row['order_status']);
        self::assertNull($row['payment_status']);
        self::assertNull($row['shipment_status']);
        self::assertSame('placed', $row['status']);
    }

    #[Test]
    public function itProjectsTheCaptureOnOrderPaymentCapturedWithoutLosingTheOrderStatus(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->captured()->create();
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('captured', $row['payment_status']);
        self::assertSame('placed', $row['order_status']);
        self::assertSame('preparing', $row['status']);
    }

    #[Test]
    public function itProjectsTheDispatchOnShipmentDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->dispatched()->create();
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('dispatched', $row['shipment_status']);
    }

    #[Test]
    public function itProjectsTheDeliveryOnShipmentDelivered(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->delivered()->create();
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('delivered', $row['shipment_status']);
    }

    #[Test]
    public function itProjectsTheCancellationWithoutLosingThePaymentAndShipmentStatus(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->create();
        $this->store($orderPayment);
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->dispatched()->create();
        $this->store($shipment);

        // When
        $order->cancel(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('cancelled', $row['order_status']);
        self::assertSame('requested', $row['payment_status']);
        self::assertSame('dispatched', $row['shipment_status']);
        self::assertSame('cancelled', $row['status']);
    }

    #[Test]
    public function itScopesTheUpdateToItsOwnOrder(): void
    {
        // Given
        $untouchedOrder = OrderTestFactory::new()->withCustomerId('customer-untouched')->create();
        $this->store($untouchedOrder);
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->captured()->create();
        $this->store($orderPayment);

        // Then
        $row = $this->fetchRow($untouchedOrder->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('customer-untouched', $row['customer_id']);
        self::assertSame('placed', $row['order_status']);
        self::assertNull($row['payment_status']);
        self::assertSame('placed', $row['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, order_status, payment_status, shipment_status, status, placed_at FROM %s WHERE order_id = :orderId',
                DbalOrderTrackingProjector::TABLE,
            ),
            ['orderId' => $orderId],
        );
    }
}
