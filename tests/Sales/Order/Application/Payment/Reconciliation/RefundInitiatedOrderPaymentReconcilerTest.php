<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment\Reconciliation;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Payment\PaymentGatewayStatus;
use Sales\Order\Application\Payment\Reconciliation\RefundInitiatedOrderPaymentReconciler;
use Sales\Tests\Order\Support\Doubles\StubPaymentGateway;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Application\Command\CommandBusInterface;
use Support\AbstractIntegrationTestCase;

final class RefundInitiatedOrderPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $orderPaymentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderPaymentFinder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesWhenRefunded(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->withReference('GLBX-REFD0001')->authorized()->captured()->refundInitiated()->create();
        $this->store($order, $orderPayment);
        $reconciler = new RefundInitiatedOrderPaymentReconciler(new StubPaymentGateway(['GLBX-REFD0001' => PaymentGatewayStatus::REFUNDED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), 'GLBX-REFD0001');

        // Then
        self::assertTrue($reconciled);
        self::assertSame(OrderPaymentStatus::REFUNDED, $this->orderPaymentFinder->ofReference('GLBX-REFD0001')->status);
    }

    #[Test]
    public function itIgnoresWhenStillRefunding(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->withReference('GLBX-PEND0002')->authorized()->captured()->refundInitiated()->create();
        $this->store($order, $orderPayment);
        $reconciler = new RefundInitiatedOrderPaymentReconciler(new StubPaymentGateway(['GLBX-PEND0002' => PaymentGatewayStatus::REFUNDING]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), 'GLBX-PEND0002');

        // Then
        self::assertFalse($reconciled);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $this->orderPaymentFinder->ofReference('GLBX-PEND0002')->status);
    }
}
