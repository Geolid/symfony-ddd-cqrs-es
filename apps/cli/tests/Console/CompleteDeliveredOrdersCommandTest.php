<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\Clock\Clock;
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
    public function itCompletesPastTheReturnWindow(): void
    {
        // Given
        $now = Clock::get()->now();
        $expired = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered($now->modify('-20 days'))
            ->create();
        $withinWindow = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered($now->modify('-2 days'))
            ->create();
        $this->store($expired, $withinWindow);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order:complete-delivered']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 order(s) completed.', $tester->getDisplay());
        self::assertSame(OrderStatus::COMPLETED, $this->orderFinder->ofId($expired->id->toString())->status);
        self::assertSame(OrderStatus::DELIVERED, $this->orderFinder->ofId($withinWindow->id->toString())->status);
    }

    #[Test]
    public function itSkipsWhenAlreadyRunning(): void
    {
        // Given
        $this->store(OrderTestFactory::new()->confirmed()->dispatched()->delivered()->create());
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = new LockFactory($store)->createLock('sales:order:complete-delivered');
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
