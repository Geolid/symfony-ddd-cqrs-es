<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\CancelShipment;

use Fulfilment\Shipping\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itCancelsWhenPending(): void
    {
        // Given
        $sourceId = ShipmentBuilder::sample('sourceId');
        $shipment = ShipmentBuilder::new()->withSourceId($sourceId)->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($sourceId));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyDelivered(): void
    {
        // Given
        $sourceId = ShipmentBuilder::sample('sourceId');
        $shipment = ShipmentBuilder::new()->withSourceId($sourceId)->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($sourceId));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $sourceId = ShipmentBuilder::sample('sourceId');

        // When
        $this->dispatch(new CancelShipment($sourceId));

        // Then
        $result = $this->finder->ofSourceOrNull($sourceId);
        self::assertNull($result);
    }
}
