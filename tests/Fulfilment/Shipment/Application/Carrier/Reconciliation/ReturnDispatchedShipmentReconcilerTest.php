<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ReturnDispatchedShipmentReconciler;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Doubles\StubCarrierGateway;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\AbstractIntegrationTestCase;

final class ReturnDispatchedShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesWhenReturnReceived(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested($returnTrackingReference)
            ->returnDispatched()
            ->create();
        $this->store($shipment);
        $reconciler = new ReturnDispatchedShipmentReconciler(new StubCarrierGateway([$returnTrackingReference => CarrierGatewayStatus::RETURN_RECEIVED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertTrue($reconciled);
        self::assertSame(ShipmentStatus::RETURN_RECEIVED, $this->shipmentFinder->ofReturnTrackingReference($returnTrackingReference)->status);
    }

    #[Test]
    public function itIgnoresWhenStillReturnDispatched(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested($returnTrackingReference)
            ->returnDispatched()
            ->create();
        $this->store($shipment);
        $reconciler = new ReturnDispatchedShipmentReconciler(new StubCarrierGateway([$returnTrackingReference => CarrierGatewayStatus::RETURN_DISPATCHED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertFalse($reconciled);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $this->shipmentFinder->ofReturnTrackingReference($returnTrackingReference)->status);
    }
}
