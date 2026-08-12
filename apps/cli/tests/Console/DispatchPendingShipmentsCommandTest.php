<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

final class DispatchPendingShipmentsCommandTest extends AbstractCliTestCase
{
    private ShipmentFinderInterface $shipmentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipmentFinder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itDispatchesEveryPendingShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'fulfilment:shipment:dispatch-pending']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 shipment(s) dispatched.', $tester->getDisplay());

        $results = iterator_to_array($this->shipmentFinder->byStatus('dispatched'));
        self::assertCount(1, $results);
    }

    #[Test]
    public function itFailsToRunWhileAlreadyRunningInAnotherProcess(): void
    {
        // Given
        ShipmentTestFactory::new()->store();
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = (new LockFactory($store))->createLock('fulfilment:shipment:dispatch-pending');
        $lock->acquire();
        $tester = $this->tester();

        try {
            // When
            $tester->run(['command' => 'fulfilment:shipment:dispatch-pending']);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());

            $results = iterator_to_array($this->shipmentFinder->byStatus('dispatched'));
            self::assertCount(0, $results);
        } finally {
            $lock->release();
        }
    }
}
