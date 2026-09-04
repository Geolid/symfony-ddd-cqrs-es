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
use Fulfilment\Shipment\Domain\ValueObject\TrackingNumber;
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
                'reference' => $event->reference,
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
                'tracking_number' => $event->trackingNumber,
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

    /**
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('reference', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('tracking_number', Types::STRING, ['length' => TrackingNumber::MAX_LENGTH, 'notnull' => false, 'default' => null]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('manifested_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('dispatched_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('cancelled_at', Types::DATETIME_IMMUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['reference'], 'fulfilment_shipment_reference_idx');
        $table->addIndex(['tracking_number'], 'fulfilment_shipment_tracking_number_idx');
    }
}
