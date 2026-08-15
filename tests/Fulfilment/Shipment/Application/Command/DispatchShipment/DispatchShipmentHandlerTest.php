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
    public function itDispatchesAManifestedShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->manifested()->store();

        // When
        $this->dispatch(new DispatchShipment($shipment->id()->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::DISPATCHED, $results[0]->status);
        self::assertNotNull($results[0]->dispatchedAt);
        self::assertNull($results[0]->trackingReference);
    }

    #[Test]
    public function itFailsWhenTheShipmentHasAlreadyLeft(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->manifested()->dispatched()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DispatchShipment($shipment->id()->toString()));
    }

    #[Test]
    public function itFailsWhenTheShipmentIsNotYetManifested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DispatchShipment($shipment->id()->toString()));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new DispatchShipment($id));
    }
}
