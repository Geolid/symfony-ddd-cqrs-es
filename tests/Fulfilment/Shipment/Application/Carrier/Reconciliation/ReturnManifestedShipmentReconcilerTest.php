<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ReturnManifestedShipmentReconciler;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ReturnManifestedShipmentReconcilerTest extends AbstractIntegrationTestCase
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
    public function itReconcilesWhenReturnDispatched(): void
    {
        // Given
        $returnTrackingReference = ShipmentBuilder::sample('returnTrackingReference')->value;
        $shipment = ShipmentBuilder::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested($returnTrackingReference)
            ->create();
        $this->store($shipment);
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(CarrierGatewayStatus::RETURN_DISPATCHED);
        $reconciler = new ReturnManifestedShipmentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertTrue($reconciled);
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillReturnManifested(): void
    {
        // Given
        $returnTrackingReference = ShipmentBuilder::sample('returnTrackingReference')->value;
        $shipment = ShipmentBuilder::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested($returnTrackingReference)
            ->create();
        $this->store($shipment);
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(CarrierGatewayStatus::REQUESTED);
        $reconciler = new ReturnManifestedShipmentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertFalse($reconciled);
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $result->status);
    }
}
