<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Doubles\StubCarrierGateway;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
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
        self::getContainer()->set(CarrierGatewayInterface::class, new StubCarrierGateway([
            'ACME-STUCK9999' => CarrierGatewayStatus::DISPATCHED,
            'ACME-DISPATCHED-STUCK' => CarrierGatewayStatus::DELIVERED,
            'ACME-RETURN-STUCK' => CarrierGatewayStatus::RETURN_DISPATCHED,
            'ACME-RETURN-DISPATCHED-STUCK' => CarrierGatewayStatus::RETURN_RECEIVED,
        ]));
        $order = OrderTestFactory::new()->create();
        $this->store($order);
        $this->store(ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()
            ->manifested('ACME-STUCK9999', $now->modify('-3 days'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-FRESH9999', $now->modify('-12 hours'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-DISPATCHED-STUCK', $now->modify('-4 days'))
            ->dispatched($now->modify('-3 days'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-DISPATCHED-FRESH', $now->modify('-4 days'))
            ->dispatched($now->modify('-12 hours'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-5 days'))
            ->dispatched($now->modify('-4 days 12 hours'))
            ->delivered($now->modify('-4 days'))
            ->returnRequested($now->modify('-4 days'))
            ->returnManifested('ACME-RETURN-STUCK', $now->modify('-3 days'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-5 days'))
            ->dispatched($now->modify('-4 days 12 hours'))
            ->delivered($now->modify('-4 days'))
            ->returnRequested($now->modify('-4 days'))
            ->returnManifested('ACME-RETURN-FRESH', $now->modify('-12 hours'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-6 days'))
            ->dispatched($now->modify('-5 days 12 hours'))
            ->delivered($now->modify('-5 days'))
            ->returnRequested($now->modify('-5 days'))
            ->returnManifested('ACME-RETURN-DISPATCHED-STUCK', $now->modify('-4 days'))
            ->returnDispatched($now->modify('-3 days'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-6 days'))
            ->dispatched($now->modify('-5 days 12 hours'))
            ->delivered($now->modify('-5 days'))
            ->returnRequested($now->modify('-5 days'))
            ->returnManifested('ACME-RETURN-DISPATCHED-FRESH', $now->modify('-4 days'))
            ->returnDispatched($now->modify('-12 hours'))
            ->create());
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('4 shipment(s) reconciled.', $tester->getDisplay());
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofTrackingReference('ACME-STUCK9999')->status);
        self::assertSame(ShipmentStatus::MANIFESTED, $this->shipmentFinder->ofTrackingReference('ACME-FRESH9999')->status);
        self::assertSame(ShipmentStatus::DELIVERED, $this->shipmentFinder->ofTrackingReference('ACME-DISPATCHED-STUCK')->status);
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofTrackingReference('ACME-DISPATCHED-FRESH')->status);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $this->shipmentFinder->ofReturnTrackingReference('ACME-RETURN-STUCK')->status);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $this->shipmentFinder->ofReturnTrackingReference('ACME-RETURN-FRESH')->status);
        self::assertSame(ShipmentStatus::RETURN_RECEIVED, $this->shipmentFinder->ofReturnTrackingReference('ACME-RETURN-DISPATCHED-STUCK')->status);
        self::assertSame(ShipmentStatus::RETURN_DISPATCHED, $this->shipmentFinder->ofReturnTrackingReference('ACME-RETURN-DISPATCHED-FRESH')->status);
    }

    #[Test]
    public function itReconcilesWhenSomeFail(): void
    {
        // Given
        $now = Clock::get()->now();
        self::getContainer()->set(CarrierGatewayInterface::class, new StubCarrierGateway(
            ['ACME-UNREACHABLE' => CarrierGatewayStatus::DISPATCHED, 'ACME-STUCK9999' => CarrierGatewayStatus::DISPATCHED],
            failingReference: 'ACME-UNREACHABLE',
        ));
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-UNREACHABLE', $now->modify('-3 days'))
            ->create());
        $this->store(ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-STUCK9999', $now->modify('-3 days'))
            ->create());
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Failed to reconcile shipment', $tester->getDisplay());
        self::assertStringContainsString('1 shipment(s) reconciled.', $tester->getDisplay());
        self::assertSame(ShipmentStatus::MANIFESTED, $this->shipmentFinder->ofTrackingReference('ACME-UNREACHABLE')->status);
        self::assertSame(ShipmentStatus::DISPATCHED, $this->shipmentFinder->ofTrackingReference('ACME-STUCK9999')->status);
    }

    #[Test]
    public function itSkipsWhenAlreadyRunning(): void
    {
        // Given
        $this->store(ShipmentTestFactory::new()->prepared()->manifested(manifestedAt: Clock::get()->now()->modify('-3 days'))->create());
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
