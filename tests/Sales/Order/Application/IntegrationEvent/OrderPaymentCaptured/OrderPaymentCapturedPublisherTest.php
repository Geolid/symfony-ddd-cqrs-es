<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPaymentCaptured;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderPaymentCaptured\OrderPaymentCapturedIntegrationEvent;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPaymentCapturedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderBuilder = OrderBuilder::new();
        $order = $orderBuilder->create();
        $this->store($order);

        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentBuilder->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOf(OrderPaymentCapturedIntegrationEvent::class);
        $shippingAddress = $order->shippingAddress->toArray();
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($orderBuilder['customerId'], $event->customerId);
        self::assertSame($shippingAddress, $event->shippingAddress);
        self::assertSame($paymentBuilder['capturedAt']->format(\DateTimeInterface::ATOM), $event->capturedAt->format(\DateTimeInterface::ATOM));
    }
}
