<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment\Reconciliation;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentGatewayStatus;
use Sales\Order\Application\Payment\Reconciliation\RefundInitiatedOrderPaymentReconciler;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RefundInitiatedOrderPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $orderPaymentFinder;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderPaymentFinder = $this->service(OrderPaymentFinderInterface::class);
        $this->commandBus = $this->service(CommandBusInterface::class);
    }

    #[Test]
    public function itReconcilesWhenRefunded(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::REFUNDED);
        $reconciler = new RefundInitiatedOrderPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertTrue($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(OrderPaymentStatus::REFUNDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenStillRefunding(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->refundInitiated();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::REFUNDING);
        $reconciler = new RefundInitiatedOrderPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertFalse($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }
}
