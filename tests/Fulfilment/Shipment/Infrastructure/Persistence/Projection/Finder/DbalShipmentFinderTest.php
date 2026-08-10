<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Finder;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DbalShipmentFinderTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itListsShipments(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();
        $this->store($shipment);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(ShipmentResult::class, $result);
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame($shipment->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFiltersShipmentsByStatus(): void
    {
        // Given
        $pending = ShipmentTestFactory::new()->create();
        $this->store($pending);
        $this->store(ShipmentTestFactory::new()->dispatched()->create());
        $this->store(ShipmentTestFactory::new()->delivered()->create());

        // When
        $results = iterator_to_array($this->finder->byStatus('pending'));

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertSame($pending->id()->toString(), $result->id);
        self::assertSame($pending->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::PENDING, $result->status);
        self::assertNull($result->trackingReference);
        self::assertNotNull($result->createdAt);
        self::assertNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
        self::assertNull($result->orderCancelledAt);
    }

    #[Test]
    public function itFiltersShipmentsByTrackingReference(): void
    {
        // Given
        $tracked = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();
        $this->store($tracked);
        $this->store(ShipmentTestFactory::new()->tracked('ACME-OTHER')->create());

        // When
        $results = iterator_to_array($this->finder->byTrackingReference('ACME-4Q7X2K9'));

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertSame($tracked->id()->toString(), $result->id);
        self::assertSame($tracked->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->createdAt);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
        self::assertNull($result->orderCancelledAt);
    }
}
