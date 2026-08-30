<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\DispatchShipmentReturn;

use Fulfilment\Shipment\Application\Command\DispatchShipmentReturn\DispatchShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class DispatchShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDispatchesReturnWhenManifested(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested($returnTrackingReference)->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofReturnTrackingReference($returnTrackingReference);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $result->status);
    }

    #[Test]
    public function itIgnoresReturnAlreadyDispatched(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested($returnTrackingReference)->returnDispatched()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofReturnTrackingReference($returnTrackingReference);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

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
