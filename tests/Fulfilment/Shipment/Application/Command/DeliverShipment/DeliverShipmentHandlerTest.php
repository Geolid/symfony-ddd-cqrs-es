<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\DeliverShipment;

use Fulfilment\Shipment\Application\Command\DeliverShipment\DeliverShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class DeliverShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDeliversADispatchedShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->store();

        // When
        $this->dispatch(new DeliverShipment($shipment->id()->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::DELIVERED, $results[0]->status);
        self::assertNotNull($results[0]->deliveredAt);
    }

    #[Test]
    public function itFailsWhenTheShipmentHasNotLeftYet(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new DeliverShipment($shipment->id()->toString()));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new DeliverShipment($id));
    }
}
