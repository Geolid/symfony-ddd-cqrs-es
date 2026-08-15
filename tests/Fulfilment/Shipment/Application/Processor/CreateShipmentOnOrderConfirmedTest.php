<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderConfirmed;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Event\OrderConfirmedIntegrationEvent;
use Sales\Order\Domain\Order;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CreateShipmentOnOrderConfirmedTest extends AbstractIntegrationTestCase
{
    private CreateShipmentOnOrderConfirmed $processor;
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CreateShipmentOnOrderConfirmed::class);
        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itOpensAShipmentOnOrderConfirmed(): void
    {
        // Given
        $order = $this->placedOrder();

        // When
        ($this->processor)($this->orderConfirmed($order));

        // Then
        $results = iterator_to_array($this->shipmentFinder, false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentId::forOrder($order->id()->toString())->toString(), $results[0]->id);
        self::assertSame($order->id()->toString(), $results[0]->orderId);
        self::assertSame(ShipmentStatus::PENDING, $results[0]->status);
        $shipment = $this->shipmentOf($order);
        self::assertSame('12 rue des Lilas', $shipment->shippingAddress()->address->street);
    }

    #[Test]
    public function itOpensASingleShipmentWhenReplayedOnOrderConfirmed(): void
    {
        // Given
        $order = $this->placedOrder();
        ($this->processor)($this->orderConfirmed($order));

        // When
        ($this->processor)($this->orderConfirmed($order));

        // Then
        self::assertCount(1, $this->shipmentFinder);
    }

    private function placedOrder(): Order
    {
        return OrderTestFactory::new()
            ->withCustomerId(Uuid::uuid7()->toString())
            ->withTotalAmountInCents(4_200)
            ->store();
    }

    private function orderConfirmed(Order $order): OrderConfirmedIntegrationEvent
    {
        return new OrderConfirmedIntegrationEvent(
            orderId: $order->id()->toString(),
            customerId: Uuid::uuid7()->toString(),
            shippingAddress: ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            confirmedAt: '2026-01-01T00:00:00+00:00',
        );
    }

    private function shipmentOf(Order $order): Shipment
    {
        return $this->service(ShipmentRepositoryInterface::class)
            ->load(ShipmentId::forOrder($order->id()->toString()));
    }
}
