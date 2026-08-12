<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{status: string, tracking_reference: ?string, cancelled_at: ?string, order_cancelled_at: ?string}
 */
final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsANewShipmentOnShipmentCreated(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // When
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->store();

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('pending', $row['status']);
    }

    #[Test]
    public function itProjectsTheCarrierReferenceOnTrackingReferenceAssigned(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // When
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->dispatched()
            ->tracked('ACME-4Q7X2K9')
            ->store();

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('dispatched', $row['status']);
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
        self::assertSame('cancelled', $row['status']);
        self::assertNotNull($row['cancelled_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame('pending', $otherRow['status']);
        self::assertNull($otherRow['cancelled_at']);
    }

    #[Test]
    public function itProjectsALaterCancellationOnOrderCancelled(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->store();
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->store();

        // When
        $order->cancel($customerId, new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $this->store($order);

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['order_cancelled_at']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT status, tracking_reference, cancelled_at, order_cancelled_at FROM %s WHERE id = :id',
                DbalShipmentProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
