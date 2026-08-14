<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\CancelShipment;

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class CancelShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsAPendingShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->store();

        // When
        $this->dispatch(new CancelShipment($shipment->id()->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::CANCELLED, $results[0]->status);
        self::assertNotNull($results[0]->cancelledAt);
    }

    #[Test]
    public function itRejectsCancellationOfAnAlreadyDeliveredShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->dispatched()->delivered()->store();

        // When
        $this->dispatch(new CancelShipment($shipment->id()->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::DELIVERED, $results[0]->status);
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->dispatch(new CancelShipment($id));
    }
}
