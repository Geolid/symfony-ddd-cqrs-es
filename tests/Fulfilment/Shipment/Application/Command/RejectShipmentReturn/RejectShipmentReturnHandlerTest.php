<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\RejectShipmentReturn;

use Fulfilment\Shipment\Application\Command\RejectShipmentReturn\RejectShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
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
    public function itRejectsAReceivedReturn(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->store();

        // When
        $this->dispatch(new RejectShipmentReturn($shipment->id()->toString(), 'item damaged beyond resale'));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::RETURN_REJECTED, $results[0]->status);
    }

    #[Test]
    public function itFailsWhenTheReturnHasNotBeenReceived(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new RejectShipmentReturn($shipment->id()->toString(), 'item damaged beyond resale'));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new RejectShipmentReturn($id, 'item damaged beyond resale'));
    }
}
