<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Reconciliation\Exception\UnsupportedShipmentStatusException;
use Fulfilment\Shipping\Application\Reconciliation\ShipmentReconciler;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Double\CarrierGatewayStubTrait;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    use CarrierGatewayStubTrait;

    private ShipmentFinderInterface $shipmentFinder;
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
        $this->commandBus = $this->service(CommandBusInterface::class);
    }

    #[Test]
    public function itDispatches(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->manifested()->create();
        $this->store($shipment);
        $reconciler = $this->reconcilerWith($this->carrierGatewayReturning(CarrierGatewayStatus::DISPATCHED));

        // When
        $reconciler->reconcile($shipment->id->toString());

        // Then
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itDelivers(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->manifested()->dispatched()->create();
        $this->store($shipment);
        $reconciler = $this->reconcilerWith($this->carrierGatewayReturning(CarrierGatewayStatus::DELIVERED));

        // When
        $reconciler->reconcile($shipment->id->toString());

        // Then
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillManifested(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->manifested()->create();
        $this->store($shipment);
        $reconciler = $this->reconcilerWith($this->carrierGatewayReturning(CarrierGatewayStatus::REQUESTED));

        // When
        $reconciler->reconcile($shipment->id->toString());

        // Then
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillDispatched(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->manifested()->dispatched()->create();
        $this->store($shipment);
        $reconciler = $this->reconcilerWith($this->carrierGatewayReturning(CarrierGatewayStatus::DISPATCHED));

        // When
        $reconciler->reconcile($shipment->id->toString());

        // Then
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenStatusUnsupported(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->create();
        $this->store($shipment);
        $reconciler = $this->reconcilerWith($this->carrierGatewayReturning(CarrierGatewayStatus::DISPATCHED));

        // Then
        $this->expectException(UnsupportedShipmentStatusException::class);

        // When
        $reconciler->reconcile($shipment->id->toString());
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = ShipmentIdFactory::new()->create()->toString();
        $reconciler = $this->reconcilerWith($this->carrierGatewayReturning(CarrierGatewayStatus::DISPATCHED));

        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $reconciler->reconcile($unknownId);
    }

    private function reconcilerWith(CarrierGatewayInterface $carrierGateway): ShipmentReconciler
    {
        return new ShipmentReconciler($this->shipmentFinder, $carrierGateway, $this->commandBus);
    }
}
