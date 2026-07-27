<?php

declare(strict_types=1);

namespace Shipping\Tests\Shipment\Infrastructure\Persistence\Projection\Projector;

use Ordering\Order\Application\Command\CancelOrder\CancelOrder;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Shipping\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Support\AbstractIntegrationTestCase;

/**
 * The two ways this composite projection reacts to Ordering, side by side:
 *
 * - OrderPlaced always precedes this Shipment's own existence, so the projection can only get
 *   the customer/total by replaying history — OrderSummaryReducer, called once at
 *   ShipmentCreated (see CreateShipmentOnOrderPlacedTest).
 * - OrderCancelled can happen at any point *after* the Shipment already exists, so the
 *   projection instead subscribes to it live — DbalShipmentProjector::onOrderCancelled — no
 *   re-reducing needed.
 */
final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFansOutALaterOrderCancellationOntoAnExistingShipment(): void
    {
        // Given — an Order with a Shipment already opened for it.
        $orderId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceOrder($orderId, 'customer-1', 2_500));
        ($this->service(CreateShipmentOnOrderPlaced::class))(new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: 'customer-1',
            totalAmountInCents: 2_500,
            placedAt: (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->format('c'),
        ));

        // When — the order is cancelled after the fact.
        $this->dispatch(new CancelOrder($orderId));

        // Then — the existing Shipment row is updated in place, not re-created.
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertNotNull($results[0]->orderCancelledAt);
    }
}
