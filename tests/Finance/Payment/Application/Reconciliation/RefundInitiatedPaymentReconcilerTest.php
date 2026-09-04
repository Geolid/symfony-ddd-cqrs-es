<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Reconciliation;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Application\Checkout\PaymentGatewayStatus;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Reconciliation\RefundInitiatedPaymentReconciler;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RefundInitiatedPaymentReconcilerTest extends AbstractIntegrationTestCase
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
    public function itReconcilesWhenRefunded(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::REFUNDED);
        $reconciler = new RefundInitiatedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertTrue($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::REFUNDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillRefunding(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::REFUNDING);
        $reconciler = new RefundInitiatedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertFalse($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::REFUND_INITIATED, $result->status);
    }
}
