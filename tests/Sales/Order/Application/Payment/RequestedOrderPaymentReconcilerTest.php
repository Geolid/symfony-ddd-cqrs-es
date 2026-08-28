<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Payment\PaymentGatewayStatus;
use Sales\Order\Application\Payment\RequestedOrderPaymentReconciler;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Tests\Order\Support\Doubles\StubPaymentGateway;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Shared\Application\Command\CommandBusInterface;
use Support\AbstractIntegrationTestCase;

final class RequestedOrderPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    private OrderPaymentFinderInterface $orderPaymentFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderPaymentFinder = $this->service(OrderPaymentFinderInterface::class);
    }

    #[Test]
    public function itReconcilesWhenAuthorized(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->withReference('GLBX-AUTH0001')->create();
        $this->store($orderPayment);
        $reconciler = new RequestedOrderPaymentReconciler(new StubPaymentGateway(['GLBX-AUTH0001' => PaymentGatewayStatus::AUTHORIZED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), 'GLBX-AUTH0001');

        // Then
        self::assertTrue($reconciled);
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $this->orderPaymentFinder->ofReference('GLBX-AUTH0001')->status);
    }

    #[Test]
    public function itReconcilesWhenFailed(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->withReference('GLBX-FAIL0001')->create();
        $this->store($orderPayment);
        $reconciler = new RequestedOrderPaymentReconciler(new StubPaymentGateway(['GLBX-FAIL0001' => PaymentGatewayStatus::DECLINED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), 'GLBX-FAIL0001');

        // Then
        self::assertTrue($reconciled);
        self::assertSame(OrderPaymentStatus::FAILED, $this->orderPaymentFinder->ofReference('GLBX-FAIL0001')->status);
    }

    #[Test]
    public function itIgnoresWhenStillPending(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->withReference('GLBX-PEND0001')->create();
        $this->store($orderPayment);
        $reconciler = new RequestedOrderPaymentReconciler(new StubPaymentGateway(['GLBX-PEND0001' => PaymentGatewayStatus::REQUESTED]), $this->service(CommandBusInterface::class));

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), 'GLBX-PEND0001');

        // Then
        self::assertFalse($reconciled);
        self::assertSame(OrderPaymentStatus::REQUESTED, $this->orderPaymentFinder->ofReference('GLBX-PEND0001')->status);
    }
}
