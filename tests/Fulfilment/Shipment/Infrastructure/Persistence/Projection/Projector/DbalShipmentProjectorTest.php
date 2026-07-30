<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Projector;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Support\AbstractIntegrationTestCase;

final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFansOutALaterOrderCancellationOntoAnExistingShipment(): void
    {
        $orderId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceOrder($orderId, 'customer-1', 2_500));
        ($this->service(CreateShipmentOnOrderPlaced::class))(new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: 'customer-1',
            totalAmountInCents: 2_500,
            placedAt: (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->format('c'),
        ));

        $this->dispatch(new CancelOrder($orderId));

        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertNotNull($results[0]->orderCancelledAt);
    }
}
