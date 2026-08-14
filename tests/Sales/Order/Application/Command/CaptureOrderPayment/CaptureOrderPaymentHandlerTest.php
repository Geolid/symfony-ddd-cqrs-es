<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CaptureOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CaptureOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCapturesARequestedPayment(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->store();

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id()->toString()));

        // Then
        $reloaded = $this->service(OrderPaymentRepositoryInterface::class)->load($orderPayment->id());
        self::assertTrue($reloaded->status()->isCaptured());
    }

    #[Test]
    public function itIgnoresAnAlreadyCapturedPayment(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->captured()->store();

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
