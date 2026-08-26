<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Tests\Order\Support\Doubles\StubPaymentGateway;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

final class ReconcileOrderPaymentsCommandTest extends AbstractCliTestCase
{
    private OrderPaymentFinderInterface $orderPaymentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderPaymentFinder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesPastTheThreshold(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-01T00:00:00+00:00'));
        self::getContainer()->set(PaymentGatewayInterface::class, new StubPaymentGateway(OrderPaymentStatus::AUTHORIZED->value));
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->create());
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-FRESH1234')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T23:55:00+00:00'))
            ->create());
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order-payment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 order payment(s) reconciled.', $tester->getDisplay());
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $this->orderPaymentFinder->ofReference('GLBX-STUCK1234')->status);
        self::assertSame(OrderPaymentStatus::REQUESTED, $this->orderPaymentFinder->ofReference('GLBX-FRESH1234')->status);
    }

    #[Test]
    public function itReconcilesWhenSomeFail(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-01T00:00:00+00:00'));
        self::getContainer()->set(PaymentGatewayInterface::class, new StubPaymentGateway(OrderPaymentStatus::AUTHORIZED->value, failingReference: 'GLBX-UNREACHABLE'));
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-UNREACHABLE')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->create());
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->create());
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order-payment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Failed to reconcile order payment', $tester->getDisplay());
        self::assertStringContainsString('1 order payment(s) reconciled.', $tester->getDisplay());
        self::assertSame(OrderPaymentStatus::REQUESTED, $this->orderPaymentFinder->ofReference('GLBX-UNREACHABLE')->status);
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $this->orderPaymentFinder->ofReference('GLBX-STUCK1234')->status);
    }

    #[Test]
    public function itSkipsWhenAlreadyRunning(): void
    {
        // Given
        $this->store(OrderPaymentTestFactory::new()->withRequestedAt(new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->create());
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = new LockFactory($store)->createLock('sales:order-payment:reconcile');
        $lock->acquire();
        $tester = $this->tester();

        try {
            // When
            $tester->run(['command' => 'sales:order-payment:reconcile']);

            // Then
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('already running in another process', $tester->getDisplay());
        } finally {
            $lock->release();
        }
    }
}
