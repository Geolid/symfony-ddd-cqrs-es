<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Fulfilment\Shipping\Application\ShipmentDirection;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipping\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipping\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipping\Domain\Event\ShipmentManifested;
use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Projection\Projector;
use Shared\Infrastructure\Projection\Projector\AbstractDbalProjector;

#[Projector('fulfilment.shipping.project_shipments')]
final readonly class DbalShipmentProjector extends AbstractDbalProjector
{
    public const string TABLE = 'fulfilment_shipping';

    #[Subscribe(ShipmentRequested::class)]
    public function onShipmentRequested(ShipmentRequested $event): void
    {
        $this->connection->insert(
            self::TABLE,
            [
                'id' => $event->id,
                'reference' => $event->reference,
                'direction' => ShipmentDirection::from($event->direction->value)->value,
                'status' => ShipmentStatus::REQUESTED->value,
                'origin' => $this->toAddressData($event->origin->toArray()),
                'destination' => $this->toAddressData($event->destination->toArray()),
                'created_at' => $event->createdAt,
            ],
            ['origin' => Types::JSON, 'destination' => Types::JSON, 'created_at' => Types::DATETIME_IMMUTABLE],
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
                'tracking_number' => $event->trackingNumber->value,
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
        $table->addColumn('direction', Types::STRING, ['length' => 8]);
        $table->addColumn('status', Types::STRING, ['length' => 10]);
        $table->addColumn('origin', Types::JSON);
        $table->addColumn('destination', Types::JSON);
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
        $table->addIndex(['reference'], 'fulfilment_shipping_reference_idx');
        $table->addIndex(['tracking_number'], 'fulfilment_shipping_tracking_number_idx');
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     *
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function toAddressData(array $address): array
    {
        return [
            'recipient_name' => $address['recipientName'],
            'street' => $address['street'],
            'postal_code' => $address['postalCode'],
            'city' => $address['city'],
            'country_code' => $address['countryCode'],
        ];
    }
}
