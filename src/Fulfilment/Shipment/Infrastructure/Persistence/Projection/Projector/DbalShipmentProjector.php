<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipment\Domain\Event\ShipmentCreated;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\TrackingReferenceAssigned;
use Fulfilment\Shipment\Domain\ShipmentStatus;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Reducer\OrderSummaryReducer;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;

#[Projector('fulfilment.shipment.shipments')]
final readonly class DbalShipmentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'fulfilment_shipment';

    public function __construct(
        Connection $connection,
        private OrderSummaryReducer $orderSummary,
    ) {
        parent::__construct($connection);
    }

    #[Subscribe(ShipmentCreated::class)]
    public function onShipmentCreated(ShipmentCreated $event): void
    {
        $order = $this->orderSummary->forOrder($event->orderId);

        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'order_id' => $event->orderId,
            'customer_id' => $order?->customerId,
            'order_total_in_cents' => $order?->totalAmountInCents,
            'status' => ShipmentStatus::PENDING->value,
            'created_at' => new \DateTimeImmutable($event->createdAt)->format('Y-m-d H:i:s'),
        ]);
    }

    #[Subscribe(ShipmentDispatched::class)]
    public function onShipmentDispatched(ShipmentDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::DISPATCHED->value,
                'dispatched_at' => new \DateTimeImmutable($event->dispatchedAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(TrackingReferenceAssigned::class)]
    public function onTrackingReferenceAssigned(TrackingReferenceAssigned $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['tracking_reference' => $event->trackingReference],
            ['id' => $event->id],
        );
    }

    #[Subscribe(ShipmentDelivered::class)]
    public function onShipmentDelivered(ShipmentDelivered $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::DELIVERED->value,
                'delivered_at' => new \DateTimeImmutable($event->deliveredAt)->format('Y-m-d H:i:s'),
            ],
            ['id' => $event->id],
        );
    }

    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function onOrderCancelled(OrderCancelledIntegrationEvent $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['order_cancelled_at' => new \DateTimeImmutable($event->cancelledAt)->format('Y-m-d H:i:s')],
            ['order_id' => $event->orderId],
        );
    }

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('customer_id', Types::STRING, ['length' => 64, 'notnull' => false, 'default' => null]);
        $table->addColumn('order_total_in_cents', Types::INTEGER, ['notnull' => false, 'default' => null]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('tracking_reference', Types::STRING, ['length' => 64, 'notnull' => false, 'default' => null]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('dispatched_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('order_cancelled_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'fulfilment_shipment_order_id_idx');
        $table->addIndex(['tracking_reference'], 'fulfilment_shipment_tracking_reference_idx');
    }
}
