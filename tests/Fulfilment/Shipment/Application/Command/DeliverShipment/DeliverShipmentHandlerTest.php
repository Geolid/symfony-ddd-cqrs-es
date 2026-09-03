<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\DeliverShipment;

use Fulfilment\Shipment\Application\Command\DeliverShipment\DeliverShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class DeliverShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDeliversWhenDispatched(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DeliverShipment($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
        self::assertNotNull($result->deliveredAt);
    }

    #[Test]
    public function itDeliversWhenManifested(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DeliverShipment($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyDelivered(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DeliverShipment($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
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
        $this->dispatch(new DeliverShipment($id));
    }

    #[Test]
    public function itFailsWhenNotManifested(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DeliverShipment($shipment->id->toString()));
    }
}
