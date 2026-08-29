<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\DispatchShipment;

use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class DispatchShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDispatchesWhenManifested(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested($trackingReference)->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipment($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofTrackingReference($trackingReference);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertNotNull($result->dispatchedAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyDispatched(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested($trackingReference)->dispatched()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipment($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofTrackingReference($trackingReference);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new DispatchShipment($id));
    }

    #[Test]
    public function itFailsWhenNotManifested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DispatchShipment($shipment->id->toString()));
    }
}
