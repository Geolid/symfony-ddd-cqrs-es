<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Reconciliation;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Application\Checkout\PaymentGatewayStatus;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Reconciliation\RequestedPaymentReconciler;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestedPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $orderPaymentFinder;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderPaymentFinder = $this->service(PaymentFinderInterface::class);
        $this->commandBus = $this->service(CommandBusInterface::class);
    }

    #[Test]
    public function itReconcilesWhenAuthorized(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::AUTHORIZED);
        $reconciler = new RequestedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertTrue($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itReconcilesWhenFailed(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::DECLINED);
        $reconciler = new RequestedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertTrue($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillPending(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::REQUESTED);
        $reconciler = new RequestedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertFalse($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::REQUESTED, $result->status);
    }
}
