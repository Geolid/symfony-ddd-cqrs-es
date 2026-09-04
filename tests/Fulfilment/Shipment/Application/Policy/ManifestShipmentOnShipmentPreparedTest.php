<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipment\Application\Policy\ManifestShipmentOnShipmentPrepared;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ManifestShipmentOnShipmentPreparedTest extends AbstractIntegrationTestCase
{
    private CarrierGatewayInterface&MockObject $carrier;

    private CommandBusInterface&MockObject $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = $this->createMock(CarrierGatewayInterface::class);
        $this->replace(CarrierGatewayInterface::class, $this->carrier);

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $this->commandBus);
    }

    #[Test]
    public function itManifests(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared();
        $shipment = $builder->create();
        $this->store($shipment);
        $trackingNumber = ShipmentBuilder::sample('trackingNumber')->value;

        $this->carrier->expects(self::once())->method('manifest')
            ->with($shipment->id->toString(), $builder['origin'], $builder['destination'])
            ->willReturn($trackingNumber);

        $this->commandBus->expects(self::once())->method('dispatch')
            ->with(new ManifestShipment($shipment->id->toString(), $trackingNumber));

        // When
        $this->trigger(ManifestShipmentOnShipmentPrepared::class, new ShipmentPrepared($shipment->id->toString(), Clock::get()->now()));
    }
}
