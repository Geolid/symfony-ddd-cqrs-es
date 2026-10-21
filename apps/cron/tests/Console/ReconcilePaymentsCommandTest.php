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

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createStub(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);

        $this->paymentFinder = $this->service(PaymentFinderInterface::class);
    }

    #[Test]
    public function itReconciles(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = PaymentFactory::new()->withRequestedAt($now->modify('-5 minutes'))->create();
        $declined = PaymentFactory::new()->withRequestedAt($now->modify('-90 minutes'))->create();
        $authorizable = PaymentFactory::new()->withRequestedAt($now->modify('-90 minutes'))->create();
        $this->paymentGateway->method('checkStatus')->willReturnMap([
            [$declined->reference->value, PaymentGatewayStatus::DECLINED],
            [$authorizable->reference->value, PaymentGatewayStatus::AUTHORIZED],
        ]);
        $this->store($fresh, $declined, $authorizable);

        // When
        $status = $this->tester()->run(['command' => 'finance:payment:reconcile']);

        // Then
        $freshResult = $this->paymentFinder->ofReference($fresh->reference->value);
        $declinedResult = $this->paymentFinder->ofReference($declined->reference->value);
        $authorizableResult = $this->paymentFinder->ofReference($authorizable->reference->value);
        self::assertSame(Command::SUCCESS, $status);
        self::assertSame(PaymentStatus::REQUESTED, $freshResult->status);
        self::assertSame(PaymentStatus::ABANDONED, $declinedResult->status);
        self::assertSame(PaymentStatus::AUTHORIZED, $authorizableResult->status);
    }

    #[Test]
    public function itReconcilesWhenGatewayFails(): void
    {
        // Given
        $now = Clock::get()->now();
        $unreachable = PaymentFactory::new()->withRequestedAt($now->modify('-90 minutes'))->create();
        $authorizable = PaymentFactory::new()->withRequestedAt($now->modify('-90 minutes'))->create();
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
