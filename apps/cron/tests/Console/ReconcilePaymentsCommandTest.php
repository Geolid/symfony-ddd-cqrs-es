<?php

declare(strict_types=1);

namespace Cron\Tests\Console;

use Cron\Tests\AbstractCronTestCase;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Console\Command\Command;

final class ReconcilePaymentsCommandTest extends AbstractCronTestCase
{
    private PaymentGatewayInterface&Stub $paymentGateway;
    private PaymentFinderInterface $paymentFinder;
    private string $threshold;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createStub(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);

        $this->paymentFinder = $this->service(PaymentFinderInterface::class);

        $threshold = self::getContainer()->getParameter('finance.payment.reconciliation_threshold');
        \assert(\is_string($threshold));
        $this->threshold = $threshold;
    }

    #[Test]
    public function itReconciles(): void
    {
        // Given
        $now = Clock::get()->now();
        $withinThreshold = PaymentFactory::new()->withRequestedAt($now->modify(\sprintf('-%s +1 second', $this->threshold)))->create();
        $pastThresholdDeclined = PaymentFactory::new()->withRequestedAt($now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $pastThresholdAuthorized = PaymentFactory::new()->withRequestedAt($now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $this->paymentGateway->method('checkStatus')->willReturnMap([
            [$pastThresholdDeclined->reference->value, PaymentGatewayStatus::DECLINED],
            [$pastThresholdAuthorized->reference->value, PaymentGatewayStatus::AUTHORIZED],
        ]);
        $this->store($withinThreshold, $pastThresholdDeclined, $pastThresholdAuthorized);

        // When
        $status = $this->tester()->run(['command' => 'finance:payment:reconcile']);

        // Then
        $withinThresholdResult = $this->paymentFinder->ofReference($withinThreshold->reference->value);
        $pastThresholdDeclinedResult = $this->paymentFinder->ofReference($pastThresholdDeclined->reference->value);
        $pastThresholdAuthorizedResult = $this->paymentFinder->ofReference($pastThresholdAuthorized->reference->value);
        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(PaymentStatus::REQUESTED, $withinThresholdResult->status);
        self::assertSame(PaymentStatus::ABANDONED, $pastThresholdDeclinedResult->status);
        self::assertSame(PaymentStatus::AUTHORIZED, $pastThresholdAuthorizedResult->status);
    }

    #[Test]
    public function itSkips(): void
    {
        // Given
        $now = Clock::get()->now();
        $payment = PaymentFactory::new()->withRequestedAt($now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $this->paymentGateway->method('checkStatus')->willReturn(PaymentGatewayStatus::AUTHORIZED);
        $this->store($payment);
        $lock = $this->lock('finance:payment:reconcile');

        try {
            // When
            $status = $this->tester()->run(['command' => 'finance:payment:reconcile']);

            // Then
            $result = $this->paymentFinder->ofReference($payment->reference->value);
            self::assertSame(Command::SUCCESS, $status);
            self::assertSame(PaymentStatus::REQUESTED, $result->status);
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function itFailsToReconcile(): void
    {
        // Given
        $now = Clock::get()->now();
        $unreachable = PaymentFactory::new()->withRequestedAt($now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $authorizable = PaymentFactory::new()->withRequestedAt($now->modify(\sprintf('-%s -1 second', $this->threshold)))->create();
        $this->paymentGateway->method('checkStatus')->willReturnCallback(
            static fn (string $reference): PaymentGatewayStatus => $reference === $unreachable->reference->value
                ? throw PaymentTransientFailureException::forReason('Provider unreachable.') : PaymentGatewayStatus::AUTHORIZED,
        );
        $this->store($unreachable, $authorizable);

        // When
        $status = $this->tester()->run(['command' => 'finance:payment:reconcile']);

        // Then
        $unreachableResult = $this->paymentFinder->ofReference($unreachable->reference->value);
        $authorizableResult = $this->paymentFinder->ofReference($authorizable->reference->value);
        self::assertSame(Command::FAILURE, $status);
        self::assertSame(PaymentStatus::REQUESTED, $unreachableResult->status);
        self::assertSame(PaymentStatus::AUTHORIZED, $authorizableResult->status);
    }
}
