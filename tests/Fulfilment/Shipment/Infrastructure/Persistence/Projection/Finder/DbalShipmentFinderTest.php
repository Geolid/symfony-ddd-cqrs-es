<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Finder;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
    public function itReadsAShipmentAsItWasHandedToTheCarrier(): void
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
        self::assertSame('dispatched', $result->status);
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

        // When
        $results = iterator_to_array($this->finder->withStatus(ShipmentStatus::PENDING));

        // Then
        self::assertSame(2, $this->finder->count());
        self::assertSame([$pending->id()->toString()], array_map(
            static fn (ShipmentResult $shipment): string => $shipment->id,
            $results,
        ));
    }

    #[Test]
    public function itFiltersShipmentsByTrackingReference(): void
    {
        // Given
        $tracked = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();
        $this->store($tracked);
        $this->store(ShipmentTestFactory::new()->tracked('ACME-OTHER')->create());

        // When
        $results = iterator_to_array($this->finder->withTrackingReference('ACME-4Q7X2K9'));

        // Then
        self::assertSame([$tracked->id()->toString()], array_map(
            static fn (ShipmentResult $shipment): string => $shipment->id,
            $results,
        ));
    }

    #[Test]
    public function itReadsAShipmentByItsOrder(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();
        $this->store($shipment);

        // When
        $result = $this->finder->ofOrder($shipment->orderId());

        // Then
        self::assertInstanceOf(ShipmentResult::class, $result);
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
    }

    #[Test]
    public function itReadsNothingForAnOrderWithoutAShipment(): void
    {
        // When
        $result = $this->finder->ofOrder(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
