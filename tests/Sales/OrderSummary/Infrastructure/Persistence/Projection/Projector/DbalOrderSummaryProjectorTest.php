<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector\DbalOrderSummaryProjector;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string, total_amount_in_cents: int|string, order_status: string, cancelled_at: ?string, payment_status: ?string, payment_amount_in_cents: int|string|null, payment_reference: ?string, payment_checkout_url: ?string, paid_at: ?string, shipment_status: ?string, tracking_reference: ?string, dispatched_at: ?string, delivered_at: ?string, status: string, placed_at: string}
 */
final class DbalOrderSummaryProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPlaced(): void
    {
        // When
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->create();
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame(4_200, (int) $row['total_amount_in_cents']);
        self::assertSame('placed', $row['order_status']);
        self::assertNull($row['payment_status']);
        self::assertNull($row['shipment_status']);
        self::assertSame('placed', $row['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->store(OrderPaymentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-ABC12345')
            ->create());

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('requested', $row['payment_status']);
        self::assertSame(2_500, (int) $row['payment_amount_in_cents']);
        self::assertSame('GLBX-ABC12345', $row['payment_reference']);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $row['payment_checkout_url']);
        self::assertNull($row['paid_at']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentCaptured(): void
    {
        // Given
        $otherCustomerId = Uuid::uuid7()->toString();
        $other = OrderTestFactory::new()->withCustomerId($otherCustomerId)->create();
        $order = OrderTestFactory::new()->create();
        $this->store($other, $order);

        // When
        $this->store(OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured()->create());

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('captured', $row['payment_status']);
        self::assertNotNull($row['paid_at']);
        self::assertSame('placed', $row['order_status']);
        self::assertSame('preparing', $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherCustomerId, $otherRow['customer_id']);
        self::assertSame('placed', $otherRow['order_status']);
        self::assertNull($otherRow['payment_status']);
        self::assertSame('placed', $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->store(ShipmentTestFactory::new()->withOrderId($order->id->toString())->prepared()->manifested()->dispatched()->create());

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('dispatched', $row['shipment_status']);
        self::assertNotNull($row['dispatched_at']);
    }

    #[Test]
    public function itProjectsOnShipmentManifested(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->store(ShipmentTestFactory::new()->withOrderId($order->id->toString())->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->create());

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('ACME-4Q7X2K9', $row['tracking_reference']);
    }

    #[Test]
    public function itProjectsOnShipmentDelivered(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->store(ShipmentTestFactory::new()->withOrderId($order->id->toString())->prepared()->manifested()->dispatched()->delivered()->create());

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('delivered', $row['shipment_status']);
        self::assertNotNull($row['delivered_at']);
    }

    #[Test]
    public function itProjectsOnOrderCancelled(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->create();
        $payment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->create();
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id->toString())->prepared()->manifested()->dispatched()->create();
        $this->store($order, $payment, $shipment);

        // When
        $order->cancel($customerId, new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('cancelled', $row['order_status']);
        self::assertNotNull($row['cancelled_at']);
        self::assertSame('requested', $row['payment_status']);
        self::assertSame('dispatched', $row['shipment_status']);
        self::assertSame('cancelled', $row['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, total_amount_in_cents, order_status, cancelled_at, payment_status, payment_amount_in_cents, payment_reference, payment_checkout_url, paid_at, shipment_status, tracking_reference, dispatched_at, delivered_at, status, placed_at FROM %s WHERE order_id = :orderId',
                DbalOrderSummaryProjector::TABLE,
            ),
            ['orderId' => $orderId],
        );
    }
}
