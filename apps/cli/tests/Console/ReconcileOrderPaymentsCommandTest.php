<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Shared\Domain\ValueObject\PostalAddress;
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
        OrderPaymentTestFactory::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->store();
        OrderPaymentTestFactory::new()
            ->withReference('GLBX-FRESH1234')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T23:55:00+00:00'))
            ->store();
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
        OrderPaymentTestFactory::new()
            ->withReference('GLBX-UNREACHABLE')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->store();
        OrderPaymentTestFactory::new()
            ->withReference('GLBX-STUCK1234')
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->store();
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
        OrderPaymentTestFactory::new()->withRequestedAt(new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->store();
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

final readonly class StubPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $status,
        private ?string $failingReference = null,
    ) {
    }

    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function void(string $reference): void
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function refund(string $reference): void
    {
        throw new \LogicException('Not needed by this test.');
    }

    public function checkStatus(string $reference): string
    {
        if ($reference === $this->failingReference) {
            throw new \RuntimeException('Provider unreachable.');
        }

        return $this->status;
    }
}
