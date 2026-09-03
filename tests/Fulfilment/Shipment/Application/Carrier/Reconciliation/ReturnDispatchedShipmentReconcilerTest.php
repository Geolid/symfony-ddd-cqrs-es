<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ReturnDispatchedShipmentReconciler;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ReturnDispatchedShipmentReconcilerTest extends AbstractIntegrationTestCase
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
    public function itReconcilesWhenReturnReceived(): void
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
            ->returnDispatched()
            ->create();
        $this->store($shipment);
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(CarrierGatewayStatus::RETURN_RECEIVED);
        $reconciler = new ReturnDispatchedShipmentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertTrue($reconciled);
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_RECEIVED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillReturnDispatched(): void
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
            ->returnDispatched()
            ->create();
        $this->store($shipment);
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(CarrierGatewayStatus::RETURN_DISPATCHED);
        $reconciler = new ReturnDispatchedShipmentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertFalse($reconciled);
        $result = $this->shipmentFinder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $result->status);
    }
}
