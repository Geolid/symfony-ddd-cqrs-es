<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\ManifestedShipmentReconciler;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Doubles\StubCarrierGateway;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\AbstractIntegrationTestCase;

final class ManifestedShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesWhenDispatched(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingReference)->create();
        $this->store($shipment);
        $reconciler = new ManifestedShipmentReconciler(new StubCarrierGateway([$trackingReference => CarrierGatewayStatus::DISPATCHED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $trackingReference);

        // Then
        self::assertTrue($reconciled);
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofId($shipment->id->toString())->status);
    }

    #[Test]
    public function itIgnoresWhenStillManifested(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingReference)->create();
        $this->store($shipment);
        $reconciler = new ManifestedShipmentReconciler(new StubCarrierGateway([$trackingReference => CarrierGatewayStatus::REQUESTED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $trackingReference);

        // Then
        self::assertFalse($reconciled);
        self::assertSame(ShipmentStatus::MANIFESTED, $this->shipmentFinder->ofId($shipment->id->toString())->status);
    }
}
