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
        $reference = ShipmentBuilder::sample('reference');
        $shipment = ShipmentBuilder::new()->withReference($reference)->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($reference));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyDelivered(): void
    {
        // Given
        $reference = ShipmentBuilder::sample('reference');
        $shipment = ShipmentBuilder::new()->withReference($reference)->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($reference));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $reference = ShipmentBuilder::sample('reference');

        // When
        $this->dispatch(new CancelShipment($reference));

        // Then
        $result = $this->finder->ofReferenceOrNull($reference);
        self::assertNull($result);
    }
}
