<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Reconciliation;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Reconciliation\DispatchedShipmentReconciler;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class DispatchedShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
        $this->commandBus = $this->service(CommandBusInterface::class);
    }

    #[Test]
    public function itReconcilesWhenDelivered(): void
    {
        // Given
        $trackingNumber = ShipmentBuilder::sample('trackingNumber')->value;
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingNumber)->dispatched()->create();
        $this->store($shipment);
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(CarrierGatewayStatus::DELIVERED);
        $reconciler = new DispatchedShipmentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $trackingNumber);

        // Then
        self::assertTrue($reconciled);
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillDispatched(): void
    {
        // Given
        $trackingNumber = ShipmentBuilder::sample('trackingNumber')->value;
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingNumber)->dispatched()->create();
        $this->store($shipment);
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(CarrierGatewayStatus::DISPATCHED);
        $reconciler = new DispatchedShipmentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $trackingNumber);

        // Then
        self::assertFalse($reconciled);
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
    }
}
