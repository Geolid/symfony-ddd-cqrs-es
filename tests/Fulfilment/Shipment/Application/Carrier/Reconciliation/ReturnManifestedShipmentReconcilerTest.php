<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ReturnManifestedShipmentReconciler;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use Fulfilment\Tests\Shipment\Support\Double\StubCarrierGateway;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ReturnManifestedShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesWhenReturnDispatched(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentBuilder::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested($returnTrackingReference)
            ->create();
        $this->store($shipment);
        $reconciler = new ReturnManifestedShipmentReconciler(new StubCarrierGateway([$returnTrackingReference => CarrierGatewayStatus::RETURN_DISPATCHED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertTrue($reconciled);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $this->shipmentFinder->ofId($shipment->id->toString())->status);
    }

    #[Test]
    public function itIgnoresWhenStillReturnManifested(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentBuilder::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested($returnTrackingReference)
            ->create();
        $this->store($shipment);
        $reconciler = new ReturnManifestedShipmentReconciler(new StubCarrierGateway([$returnTrackingReference => CarrierGatewayStatus::REQUESTED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $returnTrackingReference);

        // Then
        self::assertFalse($reconciled);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $this->shipmentFinder->ofId($shipment->id->toString())->status);
    }
}
