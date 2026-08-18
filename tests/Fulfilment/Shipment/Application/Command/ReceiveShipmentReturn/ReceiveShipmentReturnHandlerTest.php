<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\ReceiveShipmentReturn;

use Fulfilment\Shipment\Application\Command\ReceiveShipmentReturn\ReceiveShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ReceiveShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReceivesReturnWhenDispatched(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->store();

        // When
        $this->dispatch(new ReceiveShipmentReturn($shipment->id->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::RETURN_RECEIVED, $results[0]->status);
    }

    #[Test]
    public function itReceivesReturnWhenManifested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->store();

        // When
        $this->dispatch(new ReceiveShipmentReturn($shipment->id->toString()));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertSame(ShipmentStatus::RETURN_RECEIVED, $results[0]->status);
    }

    #[Test]
    public function itFailsWhenNotManifested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new ReceiveShipmentReturn($shipment->id->toString()));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ReceiveShipmentReturn($id));
    }
}
