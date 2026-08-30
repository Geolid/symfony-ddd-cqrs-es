<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Fulfilment\Shipment\Infrastructure\Projection\Projector\DbalShipmentProjector;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{customer_id: string, status: string, tracking_reference: ?string, return_tracking_reference: ?string, manifested_at: ?string, dispatched_at: ?string, delivered_at: ?string, cancelled_at: ?string, return_manifested_at: ?string, return_dispatched_at: ?string, return_received_at: ?string, return_approved_at: ?string, return_rejected_at: ?string, return_rejection_reason: ?string}
 */
final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnShipmentRequested(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withCustomerId($customerId)->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame(ShipmentStatus::REQUESTED->value, $row['status']);
        self::assertNull($row['tracking_reference']);
    }

    #[Test]
    public function itProjectsOnShipmentPrepared(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()->prepared()->create();
        $this->store($other, $shipment);

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
        $shipment = ShipmentTestFactory::new()->prepared()->create();
        $this->store($other, $shipment);

        // When
        $shipment->manifest(TrackingReference::fromString('ACME-4Q7X2K9'), Clock::get()->now());
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::MANIFESTED->value, $row['status']);
        self::assertSame('ACME-4Q7X2K9', $row['tracking_reference']);
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
        $shipment = ShipmentTestFactory::new()->prepared()->manifested('ACME-4Q7X2K9')->create();
        $this->store($other, $shipment);

        // When
        $shipment->dispatch(Clock::get()->now());
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
        $shipment = ShipmentTestFactory::new()->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->create();
        $this->store($other, $shipment);

        // When
        $shipment->deliver(Clock::get()->now());
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
        $shipment = ShipmentTestFactory::new()->cancelled()->create();
        $this->store($other, $shipment);

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
    public function itProjectsOnShipmentReturnRequested(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()
            ->create();
        $this->store($other, $shipment);

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_REQUESTED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentReturnManifested(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()->returnManifested('ACME-RETURN-4Q7X2K9')
            ->create();
        $this->store($other, $shipment);

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED->value, $row['status']);
        self::assertSame('ACME-RETURN-4Q7X2K9', $row['return_tracking_reference']);
        self::assertNotNull($row['return_manifested_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentReturnDispatched(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()->returnManifested()->returnDispatched()
            ->create();
        $this->store($other, $shipment);

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED->value, $row['status']);
        self::assertNotNull($row['return_dispatched_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentReturnReceived(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()->returnManifested()->returnDispatched()->returnReceived()
            ->create();
        $this->store($other, $shipment);

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_RECEIVED->value, $row['status']);
        self::assertNotNull($row['return_received_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentReturnApproved(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()
            ->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()
            ->returnApproved()
            ->create();
        $this->store($other, $shipment);

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_APPROVED->value, $row['status']);
        self::assertNotNull($row['return_approved_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentReturnRejected(): void
    {
        // Given
        $other = $this->otherShipment();
        $shipment = ShipmentTestFactory::new()
            ->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()
            ->returnRejected('item damaged beyond resale')
            ->create();
        $this->store($other, $shipment);

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_REJECTED->value, $row['status']);
        self::assertNotNull($row['return_rejected_at']);
        self::assertSame('item damaged beyond resale', $row['return_rejection_reason']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    private function otherShipment(): Shipment
    {
        return ShipmentTestFactory::new()->create();
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, status, tracking_reference, return_tracking_reference, manifested_at, dispatched_at, delivered_at, cancelled_at, return_manifested_at, return_dispatched_at, return_received_at, return_approved_at, return_rejected_at, return_rejection_reason FROM %s WHERE id = :id',
                DbalShipmentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
