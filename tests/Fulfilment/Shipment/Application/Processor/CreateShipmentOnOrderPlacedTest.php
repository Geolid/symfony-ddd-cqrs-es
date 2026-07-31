<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\OrderId;
use Support\AbstractIntegrationTestCase;

final class CreateShipmentOnOrderPlacedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itOpensAShipmentEnrichedWithTheOrderSummaryOnOrderPlaced(): void
    {
        // Given
        $orderId = $this->placeOrder();

        // When
        ($this->service(CreateShipmentOnOrderPlaced::class))($this->orderPlaced($orderId));

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(ShipmentId::forOrder($orderId)->toString(), $results[0]->id);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertSame('customer-1', $results[0]->customerId);
        self::assertSame(4_200, $results[0]->orderTotalInCents);
        self::assertSame('pending', $results[0]->status);
    }

    #[Test]
    public function itFreezesTheBuyerAddressOnOrderPlaced(): void
    {
        // Given
        $orderId = $this->placeOrder();

        // When
        ($this->service(CreateShipmentOnOrderPlaced::class))($this->orderPlaced($orderId));

        // Then
        $shipment = $this->service(ShipmentRepositoryInterface::class)->load(ShipmentId::forOrder($orderId));
        self::assertSame('buyer@example.com', $shipment->customerAddress());
    }

    #[Test]
    public function itOpensASingleShipmentWhenReplayedOnOrderPlaced(): void
    {
        // Given
        $orderId = $this->placeOrder();
        $processor = $this->service(CreateShipmentOnOrderPlaced::class);
        $processor($this->orderPlaced($orderId));

        // When
        $processor($this->orderPlaced($orderId));

        // Then
        self::assertCount(1, iterator_to_array($this->service(ShipmentFinderInterface::class)));
    }

    private function placeOrder(): string
    {
        $orderId = OrderId::generate()->toString();

        $this->dispatch(new PlaceOrder($orderId, 'customer-1', 4_200));

        return $orderId;
    }

    private function orderPlaced(string $orderId): OrderPlacedIntegrationEvent
    {
        return new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: 'customer-1',
            buyerAddress: 'buyer@example.com',
            totalAmountInCents: 4_200,
            placedAt: '2026-01-01T00:00:00+00:00',
        );
    }
}
