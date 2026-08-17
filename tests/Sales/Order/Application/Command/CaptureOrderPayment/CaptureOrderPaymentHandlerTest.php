<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CaptureOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CaptureOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCapturesAnAuthorizedPayment(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->withReference('GLBX-9F3K2M1P')->authorized()->store();

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id()->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::CAPTURED, $result->status);
    }

    #[Test]
    public function itIgnoresAnAlreadyCapturedPayment(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->authorized()->captured()->store();

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
