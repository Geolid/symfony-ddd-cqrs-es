<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentGatewayStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
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
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturnMap([
            ['GLBX-STUCK1234', PaymentGatewayStatus::AUTHORIZED],
            ['GLBX-REFUND-STUCK', PaymentGatewayStatus::REFUNDED],
        ]);
        self::getContainer()->set(PaymentGatewayInterface::class, $carrier);
        $stuck = OrderPaymentBuilder::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create();
        $fresh = OrderPaymentBuilder::new()
            ->withReference('GLBX-FRESH1234')
            ->withRequestedAt($now->modify('-5 minutes'))
            ->create();
        $refundStuckOrder = OrderBuilder::new()->create();
        $refundStuckRequestedAt = $now->modify('-3 days');
        $refundStuck = OrderPaymentBuilder::new()
            ->withOrderId($refundStuckOrder->id->toString())
            ->withReference('GLBX-REFUND-STUCK')
            ->withRequestedAt($refundStuckRequestedAt)
            ->authorized($refundStuckRequestedAt->modify('+10 minutes'))
            ->captured($refundStuckRequestedAt->modify('+1 day'))
            ->refundInitiated($now->modify('-90 minutes'))
            ->create();
        $refundFreshOrder = OrderBuilder::new()->create();
        $refundFreshRequestedAt = $now->modify('-3 days');
        $refundFresh = OrderPaymentBuilder::new()
            ->withOrderId($refundFreshOrder->id->toString())
            ->withReference('GLBX-REFUND-FRESH')
            ->withRequestedAt($refundFreshRequestedAt)
            ->authorized($refundFreshRequestedAt->modify('+10 minutes'))
            ->captured($refundFreshRequestedAt->modify('+1 day'))
            ->refundInitiated($now->modify('-5 minutes'))
            ->create();
        $this->store($stuck, $fresh, $refundStuckOrder, $refundStuck, $refundFreshOrder, $refundFresh);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order-payment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('2 order payment(s) reconciled.', $tester->getDisplay());
        $stuckResult = $this->orderPaymentFinder->ofReference('GLBX-STUCK1234');
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $stuckResult->status);
        $freshResult = $this->orderPaymentFinder->ofReference('GLBX-FRESH1234');
        self::assertSame(OrderPaymentStatus::REQUESTED, $freshResult->status);
        $refundStuckResult = $this->orderPaymentFinder->ofReference('GLBX-REFUND-STUCK');
        self::assertSame(OrderPaymentStatus::REFUNDED, $refundStuckResult->status);
        $refundFreshResult = $this->orderPaymentFinder->ofReference('GLBX-REFUND-FRESH');
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $refundFreshResult->status);
    }

    #[Test]
    public function itReconcilesWhenSomeFail(): void
    {
        // Given
        $now = Clock::get()->now();
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturnCallback(static function (string $reference): PaymentGatewayStatus {
            if ('GLBX-UNREACHABLE' === $reference) {
                throw new \RuntimeException('Provider unreachable.');
            }

            return PaymentGatewayStatus::AUTHORIZED;
        });
        self::getContainer()->set(PaymentGatewayInterface::class, $carrier);
        $unreachable = OrderPaymentBuilder::new()
            ->withReference('GLBX-UNREACHABLE')
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create();
        $stuck = OrderPaymentBuilder::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create();
        $this->store($unreachable, $stuck);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'sales:order-payment:reconcile']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Failed to reconcile order payment', $tester->getDisplay());
        self::assertStringContainsString('1 order payment(s) reconciled.', $tester->getDisplay());
        $unreachableResult = $this->orderPaymentFinder->ofReference('GLBX-UNREACHABLE');
        self::assertSame(OrderPaymentStatus::REQUESTED, $unreachableResult->status);
        $stuckResult = $this->orderPaymentFinder->ofReference('GLBX-STUCK1234');
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $stuckResult->status);
    }

    #[Test]
    public function itSkipsWhenAlreadyRunning(): void
    {
        // Given
        $orderPayment = OrderPaymentBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-90 minutes'))->create();
        $this->store($orderPayment);
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
