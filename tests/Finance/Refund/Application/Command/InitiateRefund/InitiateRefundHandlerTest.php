<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Command\InitiateRefund;

use Finance\Refund\Application\Command\InitiateRefund\InitiateRefund;
use Finance\Refund\Application\Finder\RequestedPayment\Exception\RequestedPaymentResultNotFoundException;
use Finance\Refund\Domain\Event\RefundInitiated;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class InitiateRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itInitiates(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);

        // When
        $this->dispatch(new InitiateRefund($paymentBuilder['orderId']));

        // Then
        $event = $this->publishedEventOf(RefundInitiated::class);
        self::assertSame($payment->id->toString(), $event->paymentId);
        self::assertSame($paymentBuilder['orderId'], $event->orderId);
        self::assertSame($paymentBuilder['amount']->cents, $event->amount->cents);
    }

    #[Test]
    public function itFailsWhenRequestedPaymentNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(RequestedPaymentResultNotFoundException::class);

        // When
        $this->dispatch(new InitiateRefund($orderId));
    }
}
