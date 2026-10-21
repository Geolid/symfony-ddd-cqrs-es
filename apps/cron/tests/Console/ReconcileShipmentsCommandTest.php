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
        $pastThresholdAt = $now->modify(\sprintf('-%s -1 second', $this->threshold));
        $withinThresholdAt = $now->modify(\sprintf('-%s +1 second', $this->threshold));
        $withinThreshold = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $withinThresholdAt)->create();
        $pastThresholdUnmoved = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $pastThresholdAt)->create();
        $pastThresholdManifested = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $pastThresholdAt)->create();
        $pastThresholdDispatched = ShipmentFactory::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify(\sprintf('-%s -1 hour', $this->threshold)))
            ->dispatched($pastThresholdAt)
            ->create();
        $this->carrierGateway->method('checkStatus')->willReturnMap([
            [$pastThresholdUnmoved->trackingNumber?->value, CarrierGatewayStatus::REQUESTED],
            [$pastThresholdManifested->trackingNumber?->value, CarrierGatewayStatus::DISPATCHED],
            [$pastThresholdDispatched->trackingNumber?->value, CarrierGatewayStatus::DELIVERED],
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
        $pastThresholdAt = $now->modify(\sprintf('-%s -1 second', $this->threshold));
        $shipment = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $pastThresholdAt)->create();
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
        $pastThresholdAt = $now->modify(\sprintf('-%s -1 second', $this->threshold));
        $unreachable = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $pastThresholdAt)->create();
        $manifested = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $pastThresholdAt)->create();
        $this->carrierGateway->method('checkStatus')->willReturnCallback(
            static fn (string $reference): CarrierGatewayStatus => $reference === $unreachable->trackingNumber?->value
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
