<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipment\Domain\Event\ShipmentRequested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnApproved;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnReceived;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRequested;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('fulfilment.shipment.project_shipments')]
final readonly class DbalShipmentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'fulfilment_shipment';

    #[Subscribe(ShipmentRequested::class)]
    public function onShipmentRequested(ShipmentRequested $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'order_id' => $event->orderId,
                'customer_id' => $event->customerId,
                'status' => ShipmentStatus::REQUESTED->value,
                'created_at' => $event->createdAt,
            ],
            ['created_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentPrepared::class)]
    public function onShipmentPrepared(ShipmentPrepared $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => ShipmentStatus::PREPARED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(ShipmentManifested::class)]
    public function onShipmentManifested(ShipmentManifested $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::MANIFESTED->value,
                'tracking_reference' => $event->trackingReference,
                'manifested_at' => $event->manifestedAt,
            ],
            ['id' => $event->id],
            ['manifested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentDispatched::class)]
    public function onShipmentDispatched(ShipmentDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::DISPATCHED->value,
                'dispatched_at' => $event->dispatchedAt,
            ],
            ['id' => $event->id],
            ['dispatched_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentDelivered::class)]
    public function onShipmentDelivered(ShipmentDelivered $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::DELIVERED->value,
                'delivered_at' => $event->deliveredAt,
            ],
            ['id' => $event->id],
            ['delivered_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentCancelled::class)]
    public function onShipmentCancelled(ShipmentCancelled $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::CANCELLED->value,
                'cancelled_at' => $event->cancelledAt,
            ],
            ['id' => $event->id],
            ['cancelled_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentReturnRequested::class)]
    public function onShipmentReturnRequested(ShipmentReturnRequested $event): void
    {
        $this->connection->update(
            self::TABLE,
            ['status' => ShipmentStatus::RETURN_REQUESTED->value],
            ['id' => $event->id],
        );
    }

    #[Subscribe(ShipmentReturnManifested::class)]
    public function onShipmentReturnManifested(ShipmentReturnManifested $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::RETURN_MANIFESTED->value,
                'return_tracking_reference' => $event->returnTrackingReference,
                'return_manifested_at' => $event->manifestedAt,
            ],
            ['id' => $event->id],
            ['return_manifested_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentReturnDispatched::class)]
    public function onShipmentReturnDispatched(ShipmentReturnDispatched $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::RETURN_DISPATCHED->value,
                'return_dispatched_at' => $event->dispatchedAt,
            ],
            ['id' => $event->id],
            ['return_dispatched_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentReturnReceived::class)]
    public function onShipmentReturnReceived(ShipmentReturnReceived $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::RETURN_RECEIVED->value,
                'return_received_at' => $event->receivedAt,
            ],
            ['id' => $event->id],
            ['return_received_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentReturnApproved::class)]
    public function onShipmentReturnApproved(ShipmentReturnApproved $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::RETURN_APPROVED->value,
                'return_approved_at' => $event->approvedAt,
            ],
            ['id' => $event->id],
            ['return_approved_at' => Types::DATETIME_IMMUTABLE],
        );
    }

    #[Subscribe(ShipmentReturnRejected::class)]
    public function onShipmentReturnRejected(ShipmentReturnRejected $event): void
    {
        $this->connection->update(
            self::TABLE,
            [
                'status' => ShipmentStatus::RETURN_REJECTED->value,
                'return_rejected_at' => $event->rejectedAt,
                'return_rejection_reason' => $event->reason,
            ],
            ['id' => $event->id],
            ['return_rejected_at' => Types::DATETIME_IMMUTABLE],
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
        $table->addColumn('customer_id', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 17]);
        $table->addColumn('tracking_reference', Types::STRING, ['length' => TrackingReference::MAX_LENGTH, 'notnull' => false, 'default' => null]);
        $table->addColumn('return_tracking_reference', Types::STRING, ['length' => TrackingReference::MAX_LENGTH, 'notnull' => false, 'default' => null]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('manifested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_manifested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_received_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_approved_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_rejected_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('return_rejection_reason', Types::STRING, ['length' => 255, 'notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'fulfilment_shipment_order_id_idx');
        $table->addIndex(['customer_id'], 'fulfilment_shipment_customer_id_idx');
        $table->addIndex(['tracking_reference'], 'fulfilment_shipment_tracking_reference_idx');
        $table->addIndex(['return_tracking_reference'], 'fulfilment_shipment_return_tracking_reference_idx');
    }
}
