<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\PrepareShipment;

use Fulfilment\Shipment\Application\Command\PrepareShipment\PrepareShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PrepareShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPrepares(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new PrepareShipment($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::PREPARED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentBuilder::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new PrepareShipment($id));
    }
}
