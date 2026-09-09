<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\VoidPaymentOnOrderCancelled;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class VoidPaymentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itVoids(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new()->authorized();
        $payment = $paymentBuilder->create();
        $order = OrderBuilder::new()->withPaymentId($payment->id->toString())->create();
        $this->store($payment, $order);

        // When
        $this->trigger(VoidPaymentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($order->id->toString(), $order->buyerId, Clock::get()->now()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::VOIDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $this->trigger(VoidPaymentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($orderId, Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
