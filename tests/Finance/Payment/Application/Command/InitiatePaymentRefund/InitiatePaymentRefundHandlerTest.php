<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\InitiatePaymentRefund;

use Finance\Payment\Application\Command\InitiatePaymentRefund\InitiatePaymentRefund;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class InitiatePaymentRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiatesRefundWhenCaptured(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentFactory = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized()->captured();
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new InitiatePaymentRefund($orderPayment->id->toString()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(PaymentStatus::REFUND_INITIATED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenUncaptured(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new InitiatePaymentRefund($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = PaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new InitiatePaymentRefund($id));
    }
}
