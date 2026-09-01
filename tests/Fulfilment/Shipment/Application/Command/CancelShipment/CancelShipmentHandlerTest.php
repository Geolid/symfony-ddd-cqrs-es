<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\CancelShipment;

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

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
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itRejectsCancellationWhenAlreadyDelivered(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentBuilder::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new CancelShipment($id));
    }
}
