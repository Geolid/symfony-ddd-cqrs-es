<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

final class CompleteDeliveredOrdersCommandTest extends AbstractCliTestCase
{
    private OrderFinderInterface $orderFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFinder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itCompletesOrdersPastTheReturnWindow(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-01T00:00:00+00:00'));
        $expired = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->store();
        $withinWindow = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-30T00:00:00+00:00'))
            ->store();
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order:complete-delivered']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 order(s) completed.', $tester->getDisplay());
        self::assertSame(OrderStatus::COMPLETED, $this->orderFinder->ofId($expired->id()->toString())->status);
        self::assertSame(OrderStatus::DELIVERED, $this->orderFinder->ofId($withinWindow->id()->toString())->status);
    }

    #[Test]
    public function itFailsToRunWhileAlreadyRunningInAnotherProcess(): void
    {
        // Given
        OrderTestFactory::new()->confirmed()->dispatched()->delivered()->store();
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = (new LockFactory($store))->createLock('sales:order:complete-delivered');
        $lock->acquire();
        $tester = $this->tester();

        try {
            // When
            $tester->run(['command' => 'sales:order:complete-delivered']);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());
        } finally {
            $lock->release();
        }
    }
}
