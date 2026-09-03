<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\InitiateOrderPaymentRefund;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\InitiateOrderPaymentRefund\InitiateOrderPaymentRefund;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class InitiateOrderPaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiatesRefundWhenCaptured(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentFactory = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(OrderPaymentStatus::REFUND_INITIATED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenUncaptured(): void
    {
        // Given
        $orderPayment = OrderPaymentBuilder::new()->create();
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
        $id = OrderPaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new InitiateOrderPaymentRefund($id));
    }
}
