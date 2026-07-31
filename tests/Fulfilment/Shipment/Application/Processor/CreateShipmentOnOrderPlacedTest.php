<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\Order;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CreateShipmentOnOrderPlacedTest extends AbstractIntegrationTestCase
{
    private CreateShipmentOnOrderPlaced $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CreateShipmentOnOrderPlaced::class);
    }

    #[Test]
    public function itOpensAShipmentEnrichedWithTheOrderSummaryOnOrderPlaced(): void
    {
        // Given
        $order = $this->placedOrder();

        // When
        ($this->processor)($this->orderPlaced($order));

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(ShipmentId::forOrder($order->id()->toString())->toString(), $results[0]->id);
        self::assertSame($order->id()->toString(), $results[0]->orderId);
        self::assertSame('customer-1', $results[0]->customerId);
        self::assertSame(4_200, $results[0]->orderTotalInCents);
        self::assertSame('pending', $results[0]->status);
        self::assertSame('buyer@example.com', $this->addressOf($order));
    }

    #[Test]
    public function itOpensASingleShipmentWhenReplayedOnOrderPlaced(): void
    {
        // Given
        $order = $this->placedOrder();
        ($this->processor)($this->orderPlaced($order));

        // When
        ($this->processor)($this->orderPlaced($order));

        // Then
        self::assertCount(1, iterator_to_array($this->service(ShipmentFinderInterface::class)));
    }

    private function placedOrder(): Order
    {
        $order = OrderTestFactory::new()
            ->withCustomerId('customer-1')
            ->withBuyerAddress('buyer@example.com')
            ->withTotalAmountInCents(4_200)
            ->create();

        $this->store($order);

        return $order;
    }

    private function orderPlaced(Order $order): OrderPlacedIntegrationEvent
    {
        return new OrderPlacedIntegrationEvent(
            orderId: $order->id()->toString(),
            customerId: 'customer-1',
            buyerAddress: 'buyer@example.com',
            lines: [['label' => 'Assorted goods', 'quantity' => 1, 'unitAmountInCents' => 4_200]],
            totalAmountInCents: 4_200,
            placedAt: '2026-01-01T00:00:00+00:00',
        );
    }

    private function addressOf(Order $order): ?string
    {
        return $this->service(ShipmentRepositoryInterface::class)
            ->load(ShipmentId::forOrder($order->id()->toString()))
            ->customerAddress();
    }
}
