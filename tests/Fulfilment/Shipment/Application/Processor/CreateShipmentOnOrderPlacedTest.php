<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Support\AbstractIntegrationTestCase;

final class CreateShipmentOnOrderPlacedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itOpensAShipmentEnrichedWithTheOrderSummary(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceOrder($orderId, 'customer-1', 4_200));

        $event = new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: 'customer-1',
            totalAmountInCents: 4_200,
            placedAt: (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->format('c'),
        );

        // When
        $this->service(CreateShipmentOnOrderPlaced::class)->onOrderPlaced($event);

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertSame('customer-1', $results[0]->customerId);
        self::assertSame(4_200, $results[0]->orderTotalInCents);
        self::assertSame('pending', $results[0]->status);
    }
}
