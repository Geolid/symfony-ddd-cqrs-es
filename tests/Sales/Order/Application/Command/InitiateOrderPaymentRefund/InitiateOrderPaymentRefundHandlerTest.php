<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\InitiateOrderPaymentRefund;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\InitiateOrderPaymentRefund\InitiateOrderPaymentRefund;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class InitiateOrderPaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiatesRefundWhenCaptured(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $paymentFactory = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentFactory->attribute('reference')->value);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenUncaptured(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderPaymentTestFactory::new()->create()->id->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($id));
    }
}
