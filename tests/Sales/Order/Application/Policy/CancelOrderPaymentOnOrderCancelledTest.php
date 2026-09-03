<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Policy\CancelOrderPaymentOnOrderCancelled;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelOrderPaymentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($orderId);
        $payment = $paymentBuilder->create();
        $this->store($payment);

        // When
        $this->trigger(CancelOrderPaymentOnOrderCancelled::class, new OrderCancelled($orderId, Clock::get()->now()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(OrderPaymentStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $this->trigger(CancelOrderPaymentOnOrderCancelled::class, new OrderCancelled($orderId, Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
