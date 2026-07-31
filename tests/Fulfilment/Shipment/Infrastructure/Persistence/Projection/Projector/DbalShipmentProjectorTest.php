<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DbalShipmentProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheOrderSummaryOnShipmentCreated(): void
    {
        // Given
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->withTotalAmountInCents(2_500)->create();
        $this->store($order);

        // When
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->create();
        $this->store($shipment);

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('customer-1', $row['customer_id']);
        self::assertSame(2_500, (int) $row['order_total_in_cents']);
        self::assertSame('pending', $row['status']);
    }

    #[Test]
    public function itProjectsALaterCancellationOnOrderCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);
        $shipment = ShipmentTestFactory::new()->withOrderId($order->id()->toString())->create();
        $this->store($shipment);

        // When
        $order->cancel(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $this->store($order);

        // Then
        $row = $this->fetchRow($shipment->id()->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['order_cancelled_at']);
    }

    /**
     * @return array<string, mixed>|false
     */
    private function fetchRow(string $id): array|false
    {
        return $this->serviceAs('doctrine.dbal.default_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT * FROM %s WHERE id = :id', DbalShipmentProjector::TABLE),
            ['id' => $id],
        );
    }
}
