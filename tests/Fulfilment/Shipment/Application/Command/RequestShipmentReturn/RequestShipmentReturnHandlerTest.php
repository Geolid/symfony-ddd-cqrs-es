<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\RequestShipmentReturn;

use Fulfilment\Shipment\Application\Command\RequestShipmentReturn\RequestShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RequestShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new RequestShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_REQUESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentBuilder::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new RequestShipmentReturn($id));
    }

    #[Test]
    public function itFailsWhenNotDelivered(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new RequestShipmentReturn($shipment->id->toString()));
    }
}
