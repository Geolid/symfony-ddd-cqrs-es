<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\CapturePaymentOnOrderDispatched;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderDispatched\OrderDispatchedIntegrationEvent;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CapturePaymentOnOrderDispatchedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCaptures(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);

        // When
        $this->trigger(CapturePaymentOnOrderDispatched::class, new OrderDispatchedIntegrationEvent($order->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
    }
}
