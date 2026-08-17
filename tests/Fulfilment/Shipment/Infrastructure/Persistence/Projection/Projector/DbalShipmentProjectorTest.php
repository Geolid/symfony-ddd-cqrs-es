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
 * @phpstan-type Row array{customer_id: string, status: string, tracking_reference: ?string, cancelled_at: ?string}
 */
final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsANewShipmentOnShipmentRequested(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->store();

        // When
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->withCustomerId($customerId)
            ->store();

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame(ShipmentStatus::REQUESTED->value, $row['status']);
        self::assertNull($row['tracking_reference']);
    }

    #[Test]
    public function itProjectsThePreparationOnShipmentPrepared(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // When
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->prepared()->store();

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::PREPARED->value, $row['status']);
    }

    #[Test]
    public function itProjectsTheCarrierReferenceOnShipmentManifested(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // When
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->prepared()
            ->manifested('ACME-4Q7X2K9')
            ->store();

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::MANIFESTED->value, $row['status']);
        self::assertSame('ACME-4Q7X2K9', $row['tracking_reference']);
    }

    #[Test]
    public function itProjectsTheCancellationOnShipmentCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $other = ShipmentTestFactory::new()->store();

        // When
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->cancelled()->store();

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(ShipmentStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ShipmentStatus::REQUESTED->value, $otherRow['status']);
        self::assertNull($otherRow['cancelled_at']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, status, tracking_reference, cancelled_at FROM %s WHERE id = :id',
                DbalShipmentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
