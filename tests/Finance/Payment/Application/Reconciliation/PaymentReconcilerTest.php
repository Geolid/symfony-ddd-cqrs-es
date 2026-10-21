<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Reconciliation;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Application\Reconciliation\Exception\UnsupportedPaymentStatusException;
use Finance\Payment\Application\Reconciliation\PaymentReconciler;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Finance\Tests\Payment\Support\Factory\PaymentIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentReconcilerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $paymentFinder;
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentFinder = $this->service(PaymentFinderInterface::class);
        $this->commandBus = $this->service(CommandBusInterface::class);
    }

    #[Test]
    public function itAuthorizes(): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);
        $reconciler = new PaymentReconciler($this->paymentFinder, $this->paymentGatewayReturning(PaymentGatewayStatus::AUTHORIZED), $this->commandBus);

        // When
        $reconciler->reconcile($payment->id->toString());

        // Then
        $result = $this->paymentFinder->ofId($payment->id->toString());
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    #[DataProvider('provideNonAuthorizedStatuses')]
    public function itAbandons(PaymentGatewayStatus $gatewayStatus): void
    {
        // Given
        $payment = PaymentFactory::new()->create();
        $this->store($payment);
        $reconciler = new PaymentReconciler($this->paymentFinder, $this->paymentGatewayReturning($gatewayStatus), $this->commandBus);

        // When
        $reconciler->reconcile($payment->id->toString());

        // Then
        $result = $this->paymentFinder->ofId($payment->id->toString());
        self::assertSame(PaymentStatus::ABANDONED, $result->status);
    }

    /**
     * @return iterable<string, array{PaymentGatewayStatus}>
     */
    public static function provideNonAuthorizedStatuses(): iterable
    {
        yield 'still requested' => [PaymentGatewayStatus::REQUESTED];
        yield 'declined' => [PaymentGatewayStatus::DECLINED];
    }

    #[Test]
    public function itFailsWhenStatusUnsupported(): void
    {
        // Given
        $payment = PaymentFactory::new()->authorized()->create();
        $this->store($payment);
        $reconciler = new PaymentReconciler($this->paymentFinder, $this->paymentGatewayReturning(PaymentGatewayStatus::AUTHORIZED), $this->commandBus);

        // Then
        $this->expectException(UnsupportedPaymentStatusException::class);

        // When
        $reconciler->reconcile($payment->id->toString());
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = PaymentIdFactory::new()->create()->toString();
        $reconciler = new PaymentReconciler($this->paymentFinder, $this->paymentGatewayReturning(PaymentGatewayStatus::AUTHORIZED), $this->commandBus);

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $reconciler->reconcile($unknownId);
    }

    private function paymentGatewayReturning(PaymentGatewayStatus $status): PaymentGatewayInterface
    {
        $paymentGateway = $this->createStub(PaymentGatewayInterface::class);
        $paymentGateway->method('checkStatus')->willReturn($status);

        return $paymentGateway;
    }
}
