<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\DispatchShipmentReturn;

use Fulfilment\Shipment\Application\Command\DispatchShipmentReturn\DispatchShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DispatchShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itDispatchesReturnWhenManifested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $result->status);
    }

    #[Test]
    public function itIgnoresReturnAlreadyDispatched(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentTestFactory::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new DispatchShipmentReturn($id));
    }

    #[Test]
    public function itFailsWhenNotManifested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DispatchShipmentReturn($shipment->id->toString()));
    }
}
