<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

final class PurgeExpiredOrderBillingAddressCommandTest extends AbstractCliTestCase
{
    private OrderFinderInterface $orderFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFinder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itPurgesEveryOrderPastItsBillingRetentionPeriod(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2036-01-01T00:00:00+00:00'));
        $order = OrderTestFactory::new()->withPlacedAt(new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order:purge-expired-billing-address']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 order(s) purged.', $tester->getDisplay());
        self::assertCount(0, iterator_to_array($this->orderFinder->byCustomer($order->customerId())));
    }

    #[Test]
    public function itFailsToRunWhileAlreadyRunningInAnotherProcess(): void
    {
        // Given
        OrderTestFactory::new()->withPlacedAt(new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->store();
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = (new LockFactory($store))->createLock('sales:order:purge-expired-billing-address');
        $lock->acquire();
        $tester = $this->tester();

        try {
            // When
            $tester->run(['command' => 'sales:order:purge-expired-billing-address']);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());
        } finally {
            $lock->release();
        }
    }
}
