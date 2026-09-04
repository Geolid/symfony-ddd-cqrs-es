<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Sales\OrderSummary\Infrastructure\Projection\Projector\DbalOrderSummaryProjector;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{customer_id: string, total_amount_in_cents: int|string, order_status: string, cancelled_at: ?string, payment_status: ?string, payment_amount_in_cents: int|string|null, payment_reference: ?string, payment_checkout_url: ?string, paid_at: ?string, shipment_status: ?string, tracking_number: ?string, dispatched_at: ?string, delivered_at: ?string, status: string, placed_at: string}
 */
final class DbalOrderSummaryProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPlaced(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame(4_200, (int) $row['total_amount_in_cents']);
        self::assertSame('placed', $row['order_status']);
        self::assertNull($row['payment_status']);
        self::assertNull($row['shipment_status']);
        self::assertSame(OrderSummaryStatus::PLACED->value, $row['status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentRequested(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->create();
        $this->store($other, $order);

        // When
        $payment = OrderPaymentBuilder::new()
            ->withOrderId($order->id->toString())
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://checkout.globex.test/pay/GLBX-ABC12345')
            ->create();
        $this->store($payment);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('requested', $row['payment_status']);
        self::assertSame(2_500, (int) $row['payment_amount_in_cents']);
        self::assertSame('GLBX-ABC12345', $row['payment_reference']);
        self::assertSame('https://checkout.globex.test/pay/GLBX-ABC12345', $row['payment_checkout_url']);
        self::assertNull($row['paid_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['payment_status']);
    }

    #[Test]
    public function itProjectsOnOrderPaymentCaptured(): void
    {
        // Given
        $otherCustomerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->withCustomerId($otherCustomerId)->create();
        $order = OrderBuilder::new()->create();
        $this->store($other, $order);

        // When
        $payment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
        $this->store($payment);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('captured', $row['payment_status']);
        self::assertNotNull($row['paid_at']);
        self::assertSame('placed', $row['order_status']);
        self::assertSame(OrderSummaryStatus::PREPARING->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherCustomerId, $otherRow['customer_id']);
        self::assertSame('placed', $otherRow['order_status']);
        self::assertNull($otherRow['payment_status']);
        self::assertSame(OrderSummaryStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentManifested(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->create();
        $this->store($other, $order);

        // When
        $shipment = ShipmentBuilder::new()->withReference($order->id->toString())->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->create();
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('ACME-4Q7X2K9', $row['tracking_number']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['tracking_number']);
    }

    #[Test]
    public function itProjectsOnShipmentDispatched(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->create();
        $this->store($other, $order);

        // When
        $shipment = ShipmentBuilder::new()->withReference($order->id->toString())->prepared()->manifested()->dispatched()->create();
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('dispatched', $row['shipment_status']);
        self::assertNotNull($row['dispatched_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['shipment_status']);
    }

    #[Test]
    public function itProjectsOnShipmentDelivered(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->create();
        $this->store($other, $order);

        // When
        $shipment = ShipmentBuilder::new()->withReference($order->id->toString())->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('delivered', $row['shipment_status']);
        self::assertNotNull($row['delivered_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['delivered_at']);
    }

    #[Test]
    public function itProjectsOnOrderCancelled(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $customerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $payment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->create();
        $shipment = ShipmentBuilder::new()->withReference($order->id->toString())->prepared()->manifested()->dispatched()->create();
        $this->store($other, $order, $payment, $shipment);

        // When
        $order->cancel($customerId, Clock::get()->now());
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame('cancelled', $row['order_status']);
        self::assertNotNull($row['cancelled_at']);
        self::assertSame('requested', $row['payment_status']);
        self::assertSame('dispatched', $row['shipment_status']);
        self::assertSame(OrderSummaryStatus::CANCELLED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame('placed', $otherRow['order_status']);
        self::assertNull($otherRow['cancelled_at']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $orderId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT customer_id, total_amount_in_cents, order_status, cancelled_at, payment_status, payment_amount_in_cents, payment_reference, payment_checkout_url, paid_at, shipment_status, tracking_number, dispatched_at, delivered_at, status, placed_at FROM %s WHERE order_id = :orderId',
                DbalOrderSummaryProjector::TABLE,
            ),
            ['orderId' => $orderId],
        );
    }
}
