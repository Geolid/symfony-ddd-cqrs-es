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

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrierGateway = $this->createStub(CarrierGatewayInterface::class);
        $this->replace(CarrierGatewayInterface::class, $this->carrierGateway);

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconciles(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshTrackingNumber = TrackingNumberFactory::new()->create();
        $manifestedTrackingNumber = TrackingNumberFactory::new()->create();
        $dispatchedTrackingNumber = TrackingNumberFactory::new()->create();
        $fresh = ShipmentFactory::new()->prepared()->manifested($freshTrackingNumber, $now->modify('-12 hours'))->create();
        $manifested = ShipmentFactory::new()->prepared()->manifested($manifestedTrackingNumber, $now->modify('-3 days'))->create();
        $dispatched = ShipmentFactory::new()->prepared()->manifested($dispatchedTrackingNumber, $now->modify('-4 days'))->dispatched($now->modify('-3 days'))->create();
        $this->carrierGateway->method('checkStatus')->willReturnMap([
            [$manifestedTrackingNumber->value, CarrierGatewayStatus::DISPATCHED],
            [$dispatchedTrackingNumber->value, CarrierGatewayStatus::DELIVERED],
        ]);
        $this->store($fresh, $manifested, $dispatched);

        // When
        $status = $this->tester()->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        $freshResult = $this->shipmentFinder->ofId($fresh->id->toString());
        $manifestedResult = $this->shipmentFinder->ofId($manifested->id->toString());
        $dispatchedResult = $this->shipmentFinder->ofId($dispatched->id->toString());
        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(ShipmentStatus::MANIFESTED, $freshResult->status);
        self::assertSame(ShipmentStatus::DISPATCHED, $manifestedResult->status);
        self::assertSame(ShipmentStatus::DELIVERED, $dispatchedResult->status);
    }

    #[Test]
    public function itReconcilesWhenGatewayFails(): void
    {
        // Given
        $now = Clock::get()->now();
        $unreachableTrackingNumber = TrackingNumberFactory::new()->create();
        $manifestedTrackingNumber = TrackingNumberFactory::new()->create();
        $unreachable = ShipmentFactory::new()->prepared()->manifested($unreachableTrackingNumber, $now->modify('-3 days'))->create();
        $manifested = ShipmentFactory::new()->prepared()->manifested($manifestedTrackingNumber, $now->modify('-3 days'))->create();
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
