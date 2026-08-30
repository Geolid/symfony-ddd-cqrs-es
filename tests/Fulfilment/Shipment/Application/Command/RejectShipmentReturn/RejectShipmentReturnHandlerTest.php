<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\RejectShipmentReturn;

use Fulfilment\Shipment\Application\Command\RejectShipmentReturn\RejectShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class RejectShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRejectsReturnWhenReceived(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new RejectShipmentReturn($shipment->id->toString(), 'item damaged beyond resale'));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_REJECTED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRejected(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnRejected()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new RejectShipmentReturn($shipment->id->toString(), 'item damaged beyond resale'));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_REJECTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new RejectShipmentReturn($id, 'item damaged beyond resale'));
    }

    #[Test]
    public function itFailsWhenNotReceived(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new RejectShipmentReturn($shipment->id->toString(), 'item damaged beyond resale'));
    }
}
