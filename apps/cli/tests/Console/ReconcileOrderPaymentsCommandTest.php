<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentGatewayStatus;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Tests\Order\Support\Doubles\StubPaymentGateway;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\Clock\Clock;
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
        $now = Clock::get()->now();
        self::getContainer()->set(PaymentGatewayInterface::class, new StubPaymentGateway([
            'GLBX-STUCK1234' => PaymentGatewayStatus::AUTHORIZED,
            'GLBX-REFUND-STUCK' => PaymentGatewayStatus::REFUNDED,
        ]));
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create());
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-FRESH1234')
            ->withRequestedAt($now->modify('-5 minutes'))
            ->create());
        $refundStuckOrder = OrderTestFactory::new()->create();
        $this->store($refundStuckOrder);
        $refundStuckRequestedAt = $now->modify('-3 days');
        $this->store(OrderPaymentTestFactory::new()
            ->withOrderId($refundStuckOrder->id->toString())
            ->withReference('GLBX-REFUND-STUCK')
            ->withRequestedAt($refundStuckRequestedAt)
            ->authorized($refundStuckRequestedAt->modify('+10 minutes'))
            ->captured($refundStuckRequestedAt->modify('+1 day'))
            ->refundInitiated($now->modify('-90 minutes'))
            ->create());
        $refundFreshOrder = OrderTestFactory::new()->create();
        $this->store($refundFreshOrder);
        $refundFreshRequestedAt = $now->modify('-3 days');
        $this->store(OrderPaymentTestFactory::new()
            ->withOrderId($refundFreshOrder->id->toString())
            ->withReference('GLBX-REFUND-FRESH')
            ->withRequestedAt($refundFreshRequestedAt)
            ->authorized($refundFreshRequestedAt->modify('+10 minutes'))
            ->captured($refundFreshRequestedAt->modify('+1 day'))
            ->refundInitiated($now->modify('-5 minutes'))
            ->create());
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order-payment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('2 order payment(s) reconciled.', $tester->getDisplay());
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $this->orderPaymentFinder->ofReference('GLBX-STUCK1234')->status);
        self::assertSame(OrderPaymentStatus::REQUESTED, $this->orderPaymentFinder->ofReference('GLBX-FRESH1234')->status);
        self::assertSame(OrderPaymentStatus::REFUNDED, $this->orderPaymentFinder->ofReference('GLBX-REFUND-STUCK')->status);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $this->orderPaymentFinder->ofReference('GLBX-REFUND-FRESH')->status);
    }

    #[Test]
    public function itReconcilesWhenSomeFail(): void
    {
        // Given
        $now = Clock::get()->now();
        self::getContainer()->set(PaymentGatewayInterface::class, new StubPaymentGateway(
            ['GLBX-UNREACHABLE' => PaymentGatewayStatus::AUTHORIZED, 'GLBX-STUCK1234' => PaymentGatewayStatus::AUTHORIZED],
            failingReference: 'GLBX-UNREACHABLE',
        ));
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-UNREACHABLE')
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create());
        $this->store(OrderPaymentTestFactory::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt($now->modify('-90 minutes'))
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
        $this->store(OrderPaymentTestFactory::new()->withRequestedAt(Clock::get()->now()->modify('-90 minutes'))->create());
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
