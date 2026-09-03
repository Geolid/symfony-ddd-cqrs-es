<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CaptureOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CaptureOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentFactory = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(OrderPaymentStatus::CAPTURED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCaptured(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
