<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\PrepareShipment;

use Fulfilment\Shipping\Application\Command\PrepareShipment\PrepareShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PrepareShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPrepares(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->create();
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
        $id = ShipmentFactory::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new PrepareShipment($id));
    }
}
