<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string, status: string, tracking_reference: ?string, return_tracking_reference: ?string, cancelled_at: ?string, return_dispatched_at: ?string, return_received_at: ?string, return_approved_at: ?string, return_rejected_at: ?string, return_rejection_reason: ?string}
 */
final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnShipmentRequested(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->withCustomerId($customerId)
            ->create();

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
        $order = OrderTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id->toString())->prepared()->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::PREPARED->value, $row['status']);
    }

    #[Test]
    public function itProjectsOnShipmentManifested(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()
            ->manifested('ACME-4Q7X2K9')
            ->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::MANIFESTED->value, $row['status']);
        self::assertSame('ACME-4Q7X2K9', $row['tracking_reference']);
    }

    #[Test]
    public function itProjectsOnShipmentCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id->toString())->cancelled()->create();

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
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()
            ->create();

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
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()->returnManifested('ACME-RETURN-4Q7X2K9')
            ->create();

        // When
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED->value, $row['status']);
        self::assertSame('ACME-RETURN-4Q7X2K9', $row['return_tracking_reference']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnShipmentReturnDispatched(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()->returnManifested()->returnDispatched()
            ->create();

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
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()->manifested()->dispatched()->delivered()
            ->returnRequested()->returnManifested()->returnDispatched()->returnReceived()
            ->create();

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
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()
            ->returnApproved()
            ->create();

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
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()
            ->returnRejected('item damaged beyond resale')
            ->create();

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

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, status, tracking_reference, return_tracking_reference, cancelled_at, return_dispatched_at, return_received_at, return_approved_at, return_rejected_at, return_rejection_reason FROM %s WHERE id = :id',
                DbalShipmentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
