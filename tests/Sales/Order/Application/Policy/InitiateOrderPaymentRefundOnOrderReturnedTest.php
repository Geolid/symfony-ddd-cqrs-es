<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Policy\InitiateOrderPaymentRefundOnOrderReturned;
use Sales\Order\Domain\Event\OrderReturned;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class InitiateOrderPaymentRefundOnOrderReturnedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiates(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentBuilder->create();
        $this->store($order, $orderPayment);

        // When
        $this->trigger(InitiateOrderPaymentRefundOnOrderReturned::class, new OrderReturned($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }
}
