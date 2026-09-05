<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Command\InitiateRefund;

use Finance\Refund\Application\Command\InitiateRefund\InitiateRefund;
use Finance\Refund\Application\Exception\PlacedPaymentResultNotFoundException;
use Finance\Refund\Domain\Repository\RefundRepositoryInterface;
use Finance\Refund\Domain\ValueObject\RefundId;
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
        $refund = $this->service(RefundRepositoryInterface::class)->load(RefundId::forPayment($payment->id->toString()));
        self::assertSame($payment->id->toString(), $refund->paymentId);
        self::assertSame($paymentBuilder['orderId'], $refund->orderId);
        self::assertSame($paymentBuilder['amount']->cents, $refund->amountInCents);
    }

    #[Test]
    public function itIgnoresWhenAlreadyInitiated(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);
        $this->dispatch(new InitiateRefund($paymentBuilder['orderId']));

        // When
        $this->dispatch(new InitiateRefund($paymentBuilder['orderId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenPlacedPaymentNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PlacedPaymentResultNotFoundException::class);

        // When
        $this->dispatch(new InitiateRefund($orderId));
    }
}
