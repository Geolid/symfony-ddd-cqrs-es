<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\DispatchShipment;

use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Enum\AppShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DispatchShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDispatchesAPendingShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new DispatchShipment($shipment->id()->toString()));

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame(AppShipmentStatus::DISPATCHED, $results[0]->status);
        self::assertNotNull($results[0]->dispatchedAt);
        self::assertNull($results[0]->trackingReference);
    }

    #[Test]
    public function itFailsWhenTheShipmentHasAlreadyLeft(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->dispatched()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DispatchShipment($shipment->id()->toString()));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new DispatchShipment(ShipmentId::generate()->toString()));
    }
}
