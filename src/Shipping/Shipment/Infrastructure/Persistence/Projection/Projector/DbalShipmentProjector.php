<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\Projection\Projector\AbstractDbalProjector;
use Shipping\Shipment\Domain\Event\ShipmentCreated;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Event\ShipmentDispatched;
use Shipping\Shipment\Domain\ShipmentStatus;

#[Projector('shipping.shipment')]
final readonly class DbalShipmentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'shipping_shipment';

    #[Subscribe(ShipmentCreated::class)]
    public function onShipmentCreated(ShipmentCreated $event): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $event->id,
            'order_id' => $event->orderId,
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
     * @codeCoverageIgnore
     */
    protected function configureSchema(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('order_id', Types::STRING, ['length' => 36]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('dispatched_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addColumn('delivered_at', Types::DATETIME_MUTABLE, ['notnull' => false, 'default' => null]);
        $table->addPrimaryKeyConstraint(
            PrimaryKeyConstraint::editor()
                ->setColumnNames(UnqualifiedName::unquoted('id'))
                ->create(),
        );
        $table->addIndex(['order_id'], 'shipping_shipment_order_id_idx');
    }
}
