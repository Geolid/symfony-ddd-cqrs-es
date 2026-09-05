<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Command\FailRefund;

use Finance\Refund\Application\Command\FailRefund\FailRefund;
use Finance\Refund\Application\Finder\Refund\RefundFinderInterface;
use Finance\Refund\Application\Finder\RequestedPayment\Exception\RequestedPaymentResultNotFoundException;
use Finance\Refund\Application\RefundStatus;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class FailRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFailsWhenInitiated(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);
        $refund = RefundBuilder::new()->withPaymentId($payment->id->toString())->withOrderId($paymentBuilder['orderId'])->create();
        $this->store($refund);

        // When
        $this->dispatch(new FailRefund($paymentBuilder['orderId']));

        // Then
        $result = $this->service(RefundFinderInterface::class)->ofId($refund->id->toString());
        self::assertSame(RefundStatus::FAILED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyConfirmed(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);
        $refund = RefundBuilder::new()->withPaymentId($payment->id->toString())->withOrderId($paymentBuilder['orderId'])->confirmed()->create();
        $this->store($refund);

        // When
        $this->dispatch(new FailRefund($paymentBuilder['orderId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenRequestedPaymentNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(RequestedPaymentResultNotFoundException::class);

        // When
        $this->dispatch(new FailRefund($orderId));
    }

    #[Test]
    public function itFailsWhenRefundNotFound(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);

        // Then
        $this->expectException(RefundNotFoundException::class);

        // When
        $this->dispatch(new FailRefund($paymentBuilder['orderId']));
    }
}
