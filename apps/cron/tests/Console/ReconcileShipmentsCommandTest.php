<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Cron\Tests\AbstractCronTestCase;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierTransientFailureException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Fulfilment\Tests\Shipping\Support\Factory\TrackingNumberFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Console\Command\Command;

final class ReconcileShipmentsCommandTest extends AbstractCronTestCase
{
    private CarrierGatewayInterface&Stub $carrierGateway;
    private ShipmentFinderInterface $shipmentFinder;
    private string $threshold;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrierGateway = $this->createStub(CarrierGatewayInterface::class);
        $this->replace(CarrierGatewayInterface::class, $this->carrierGateway);

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);

        $threshold = self::getContainer()->getParameter('fulfilment.shipping.reconciliation_threshold');
        \assert(\is_string($threshold));
        $this->threshold = $threshold;
    }

    #[Test]
    public function itReconciles(): void
    {
        // Given
        $now = Clock::get()->now();
        $withinThresholdTrackingNumber = TrackingNumberFactory::new()->create();
        $unmovedTrackingNumber = TrackingNumberFactory::new()->create();
        $manifestedTrackingNumber = TrackingNumberFactory::new()->create();
        $dispatchedTrackingNumber = TrackingNumberFactory::new()->create();
        $withinThreshold = ShipmentFactory::new()->prepared()->manifested($withinThresholdTrackingNumber, $now->modify(\sprintf('-%s +1 second', $this->threshold)))->create();
        $pastThresholdUnmoved = ShipmentFactory::new()->prepared()->manifested($unmovedTrackingNumber, $now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $pastThresholdManifested = ShipmentFactory::new()->prepared()->manifested($manifestedTrackingNumber, $now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $pastThresholdDispatched = ShipmentFactory::new()
            ->prepared()
            ->manifested($dispatchedTrackingNumber, $now->modify(\sprintf('-%s -1 hour', $this->threshold)))
            ->dispatched($now->modify(\sprintf('-%s -1 second', $this->threshold)))
            ->create();
        $this->carrierGateway->method('checkStatus')->willReturnMap([
            [$unmovedTrackingNumber->value, CarrierGatewayStatus::REQUESTED],
            [$manifestedTrackingNumber->value, CarrierGatewayStatus::DISPATCHED],
            [$dispatchedTrackingNumber->value, CarrierGatewayStatus::DELIVERED],
        ]);
        $this->store($withinThreshold, $pastThresholdUnmoved, $pastThresholdManifested, $pastThresholdDispatched);

        // When
        $status = $this->tester()->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        $withinThresholdResult = $this->shipmentFinder->ofId($withinThreshold->id->toString());
        $pastThresholdUnmovedResult = $this->shipmentFinder->ofId($pastThresholdUnmoved->id->toString());
        $pastThresholdManifestedResult = $this->shipmentFinder->ofId($pastThresholdManifested->id->toString());
        $pastThresholdDispatchedResult = $this->shipmentFinder->ofId($pastThresholdDispatched->id->toString());
        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(ShipmentStatus::MANIFESTED, $withinThresholdResult->status);
        self::assertSame(ShipmentStatus::MANIFESTED, $pastThresholdUnmovedResult->status);
        self::assertSame(ShipmentStatus::DISPATCHED, $pastThresholdManifestedResult->status);
        self::assertSame(ShipmentStatus::DELIVERED, $pastThresholdDispatchedResult->status);
    }

    #[Test]
    public function itSkips(): void
    {
        // Given
        $now = Clock::get()->now();
        $trackingNumber = TrackingNumberFactory::new()->create();
        $shipment = ShipmentFactory::new()->prepared()->manifested($trackingNumber, $now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $this->carrierGateway->method('checkStatus')->willReturn(CarrierGatewayStatus::DISPATCHED);
        $this->store($shipment);
        $lock = $this->lock('fulfilment:shipment:reconcile');

        try {
            // When
            $status = $this->tester()->run(['command' => 'fulfilment:shipment:reconcile']);

            // Then
            $result = $this->shipmentFinder->ofId($shipment->id->toString());
            self::assertSame(Command::SUCCESS, $status);
            self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function itFailsToReconcile(): void
    {
        // Given
        $now = Clock::get()->now();
        $unreachableTrackingNumber = TrackingNumberFactory::new()->create();
        $manifestedTrackingNumber = TrackingNumberFactory::new()->create();
        $unreachable = ShipmentFactory::new()->prepared()->manifested($unreachableTrackingNumber, $now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $manifested = ShipmentFactory::new()->prepared()->manifested($manifestedTrackingNumber, $now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $this->carrierGateway->method('checkStatus')->willReturnCallback(
            static fn (string $reference): CarrierGatewayStatus => $reference === $unreachableTrackingNumber->value
                ? throw CarrierTransientFailureException::forReason('Carrier unreachable.') : CarrierGatewayStatus::DISPATCHED,
        );
        $this->store($unreachable, $manifested);

        // When
        $status = $this->tester()->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        $unreachableResult = $this->shipmentFinder->ofId($unreachable->id->toString());
        $manifestedResult = $this->shipmentFinder->ofId($manifested->id->toString());
        self::assertSame(Command::FAILURE, $status);
        self::assertSame(ShipmentStatus::MANIFESTED, $unreachableResult->status);
        self::assertSame(ShipmentStatus::DISPATCHED, $manifestedResult->status);
    }
}
