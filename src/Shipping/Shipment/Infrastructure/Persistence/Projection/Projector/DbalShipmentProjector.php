<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Ordering\Order\Application\Event\OrderCancelledIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shipping\Shipment\Domain\Event\ShipmentCreated;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Event\ShipmentDispatched;
use Shipping\Shipment\Domain\ShipmentStatus;
use Shipping\Shipment\Infrastructure\Persistence\Projection\Reducer\OrderSummaryReducer;

#[Projector('shipping.shipment.shipments')]
final readonly class DbalShipmentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shipping_shipment';

    public function __construct(
        Connection $connection,
        private OrderSummaryReducer $orderSummary,
    ) {
        parent::__construct($connection);
    }

    #[Subscribe(ShipmentCreated::class)]
    public function onShipmentCreated(ShipmentCreated $event): void
    {
        // Denormalized from Ordering's Integration Event stream at fold time — a composite
        // read model, not a live cross-BC join (deptrac_bc.yaml still forbids Shipping from
        // reaching Ordering's Domain/Repository layer; this only reads Ordering's public
        // Integration Event contract, same as the CreateShipmentOnOrderPlaced Processor does).
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

    /**
     * The live fan-out side of the pattern: unlike OrderPlaced (which always precedes this
     * Shipment's own existence, so OrderSummaryReducer has to replay history for it), a
     * cancellation can happen at any point after the Shipment already exists — so this
     * projection subscribes to it directly instead of re-reducing the whole stream. A shipment
     * not found for the order is a no-op: cancelling before any shipment was ever created.
     */
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
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('dispatched_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('order_cancelled_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'shipping_shipment_order_id_idx');
    }
}
