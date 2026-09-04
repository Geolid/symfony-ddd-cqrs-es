<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

final class ReconcileShipmentsCommandTest extends AbstractCliTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesPastTheThreshold(): void
    {
        // Given
        $now = Clock::get()->now();
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturnMap([
            ['ACME-STUCK9999', CarrierGatewayStatus::DISPATCHED],
            ['ACME-DISPATCHED-STUCK', CarrierGatewayStatus::DELIVERED],
        ]);
        self::getContainer()->set(CarrierGatewayInterface::class, $carrier);
        $order = OrderBuilder::new()->create();
        $stuck9999 = ShipmentBuilder::new()
            ->withReference($order->id->toString())
            ->prepared()
            ->manifested('ACME-STUCK9999', $now->modify('-3 days'))
            ->create();
        $fresh9999 = ShipmentBuilder::new()
            ->prepared()
            ->manifested('ACME-FRESH9999', $now->modify('-12 hours'))
            ->create();
        $dispatchedStuck = ShipmentBuilder::new()
            ->prepared()
            ->manifested('ACME-DISPATCHED-STUCK', $now->modify('-4 days'))
            ->dispatched($now->modify('-3 days'))
            ->create();
        $dispatchedFresh = ShipmentBuilder::new()
            ->prepared()
            ->manifested('ACME-DISPATCHED-FRESH', $now->modify('-4 days'))
            ->dispatched($now->modify('-12 hours'))
            ->create();
        $this->store($order, $stuck9999, $fresh9999, $dispatchedStuck, $dispatchedFresh);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('2 shipment(s) reconciled.', $tester->getDisplay());
        $stuck9999Result = $this->shipmentFinder->ofId($stuck9999->id->toString());
        self::assertSame(ShipmentStatus::DISPATCHED, $stuck9999Result->status);
        $fresh9999Result = $this->shipmentFinder->ofId($fresh9999->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $fresh9999Result->status);
        $dispatchedStuckResult = $this->shipmentFinder->ofId($dispatchedStuck->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $dispatchedStuckResult->status);
        $dispatchedFreshResult = $this->shipmentFinder->ofId($dispatchedFresh->id->toString());
        self::assertSame(ShipmentStatus::DISPATCHED, $dispatchedFreshResult->status);
    }

    #[Test]
    public function itReconcilesWhenSomeFail(): void
    {
        // Given
        $now = Clock::get()->now();
        $carrier = $this->createStub(CarrierGatewayInterface::class);
        $carrier->method('checkStatus')->willReturnCallback(static function (string $reference): CarrierGatewayStatus {
            if ('ACME-UNREACHABLE' === $reference) {
                throw new \RuntimeException('Carrier unreachable.');
            }

            return CarrierGatewayStatus::DISPATCHED;
        });
        self::getContainer()->set(CarrierGatewayInterface::class, $carrier);
        $unreachable = ShipmentBuilder::new()
            ->prepared()
            ->manifested('ACME-UNREACHABLE', $now->modify('-3 days'))
            ->create();
        $stuck9999 = ShipmentBuilder::new()
            ->prepared()
            ->manifested('ACME-STUCK9999', $now->modify('-3 days'))
            ->create();
        $this->store($unreachable, $stuck9999);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Failed to reconcile shipment', $tester->getDisplay());
        self::assertStringContainsString('1 shipment(s) reconciled.', $tester->getDisplay());
        $unreachableResult = $this->shipmentFinder->ofId($unreachable->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $unreachableResult->status);
        $stuck9999Result = $this->shipmentFinder->ofId($stuck9999->id->toString());
        self::assertSame(ShipmentStatus::DISPATCHED, $stuck9999Result->status);
    }

    #[Test]
    public function itSkipsWhenAlreadyRunning(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested(manifestedAt: Clock::get()->now()->modify('-3 days'))->create();
        $this->store($shipment);
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = new LockFactory($store)->createLock('fulfilment:shipment:reconcile');
        $lock->acquire();
        $tester = $this->tester();

        try {
            // When
            $tester->run(['command' => 'fulfilment:shipment:reconcile']);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());
        } finally {
            $lock->release();
        }
    }
}
