<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Carrier\Reconciliation;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Carrier\Reconciliation\DispatchedShipmentReconciler;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use Fulfilment\Tests\Shipment\Support\Double\StubCarrierGateway;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class DispatchedShipmentReconcilerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesWhenDelivered(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingReference)->dispatched()->create();
        $this->store($shipment);
        $reconciler = new DispatchedShipmentReconciler(new StubCarrierGateway([$trackingReference => CarrierGatewayStatus::DELIVERED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $trackingReference);

        // Then
        self::assertTrue($reconciled);
        self::assertSame(ShipmentStatus::DELIVERED, $this->shipmentFinder->ofId($shipment->id->toString())->status);
    }

    #[Test]
    public function itIgnoresWhenStillDispatched(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingReference)->dispatched()->create();
        $this->store($shipment);
        $reconciler = new DispatchedShipmentReconciler(new StubCarrierGateway([$trackingReference => CarrierGatewayStatus::DISPATCHED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($shipment->id->toString(), $trackingReference);

        // Then
        self::assertFalse($reconciled);
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofId($shipment->id->toString())->status);
    }
}
