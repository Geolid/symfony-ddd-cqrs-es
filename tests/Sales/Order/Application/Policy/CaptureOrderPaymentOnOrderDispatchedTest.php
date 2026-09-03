<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Policy\CaptureOrderPaymentOnOrderDispatched;
use Sales\Order\Domain\Event\OrderDispatched;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CaptureOrderPaymentOnOrderDispatchedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCaptures(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);

        // When
        $this->trigger(CaptureOrderPaymentOnOrderDispatched::class, new OrderDispatched($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(OrderPaymentStatus::CAPTURED, $result->status);
    }
}
