<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Infrastructure\Projection\Projector\DbalShipmentProjector;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{order_id: string, status: string, origin: string, destination: string, tracking_number: ?string, manifested_at: ?string, dispatched_at: ?string, delivered_at: ?string, cancelled_at: ?string, erasure_status: string}
 */
final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnShipmentRequested(): void
    {
        // Given
        $builder = ShipmentBuilder::new();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['orderId'], $row['order_id']);
        self::assertSame(ShipmentStatus::REQUESTED->value, $row['status']);
        self::assertSame(SnakeCaseKeys::from($builder['origin']->toArray()), $this->decoded($row['origin']));
        self::assertSame(SnakeCaseKeys::from($builder['destination']->toArray()), $this->decoded($row['destination']));
        self::assertNull($row['tracking_number']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
    }

    #[Test]
    public function itProjectsOnShipmentPrepared(): void
    {
        // Given
        $other = $this->otherShipment();
        $this->store($other);

        $builder = ShipmentBuilder::new()->prepared();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::PREPARED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentManifested(): void
    {
        // Given
        $other = $this->otherShipment();
        $this->store($other);

        $builder = ShipmentBuilder::new()->prepared()->manifested();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::MANIFESTED->value, $row['status']);
        self::assertSame($builder['trackingNumber']->value, $row['tracking_number']);
        self::assertNotNull($row['manifested_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentDispatched(): void
    {
        // Given
        $other = $this->otherShipment();
        $this->store($other);

        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::DISPATCHED->value, $row['status']);
        self::assertNotNull($row['dispatched_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentDelivered(): void
    {
        // Given
        $other = $this->otherShipment();
        $this->store($other);

        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::DELIVERED->value, $row['status']);
        self::assertNotNull($row['delivered_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentCancelled(): void
    {
        // Given
        $other = $this->otherShipment();
        $this->store($other);

        $shipment = ShipmentBuilder::new()->cancelled()->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
        self::assertNull($otherRow['cancelled_at']);
    }

    #[Test]
    public function itProjectsOnShipmentErasureApproved(): void
    {
        // Given
        $other = $this->otherShipment();
        $this->store($other);

        $shipment = ShipmentBuilder::new()->erasureApproved()->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::APPROVED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itProjectsOnShipmentErased(): void
    {
        // Given
        $other = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($other);

        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->erasureApproved()->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::ERASED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    private function otherShipment(): Shipment
    {
        return ShipmentBuilder::new()->create();
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function decoded(string $json): array
    {
        /** @var array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT order_id, status, origin, destination, tracking_number, manifested_at, dispatched_at, delivered_at, cancelled_at, erasure_status FROM %s WHERE id = :id',
                DbalShipmentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
