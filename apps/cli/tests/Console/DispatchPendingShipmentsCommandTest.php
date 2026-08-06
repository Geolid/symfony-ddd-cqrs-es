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
    #[Test]
    public function itDispatchesEveryPendingShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // When
        $tester = $this->tester('fulfilment:shipment:dispatch-pending');
        $tester->execute([]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 shipment(s) dispatched.', $tester->getDisplay());

        $results = iterator_to_array($this->service(ShipmentFinderInterface::class)->withStatus('dispatched'));
        self::assertCount(1, $results);
    }

    #[Test]
    public function itDispatchesMoreThanOnePageOfPendingShipments(): void
    {
        // Given
        for ($i = 0; $i < 101; ++$i) {
            $this->store(ShipmentTestFactory::new()->create());
        }

        // When
        $tester = $this->tester('fulfilment:shipment:dispatch-pending');
        $tester->execute([]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('101 shipment(s) dispatched.', $tester->getDisplay());

        $results = iterator_to_array($this->service(ShipmentFinderInterface::class)->withStatus('dispatched'));
        self::assertCount(101, $results);
    }

    #[Test]
    public function itFailsToRunWhileAlreadyRunningInAnotherProcess(): void
    {
        // Given
        $this->store(ShipmentTestFactory::new()->create());
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = (new LockFactory($store))->createLock('fulfilment:shipment:dispatch-pending');
        $lock->acquire();

        try {
            // When
            $tester = $this->tester('fulfilment:shipment:dispatch-pending');
            $tester->execute([]);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());

            $results = iterator_to_array($this->service(ShipmentFinderInterface::class)->withStatus('dispatched'));
            self::assertCount(0, $results);
        } finally {
            $lock->release();
        }
    }
}
