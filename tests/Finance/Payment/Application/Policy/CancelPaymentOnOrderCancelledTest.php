<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\CancelPaymentOnOrderCancelled;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelPaymentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($orderId);
        $payment = $paymentBuilder->create();
        $this->store($payment);

        // When
        $this->trigger(CancelPaymentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($orderId, Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $this->trigger(CancelPaymentOnOrderCancelled::class, new OrderCancelledIntegrationEvent($orderId, Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
