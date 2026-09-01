<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     orderId: string,
 *     amount: Money,
 *     reference: PaymentReference,
 *     checkoutUrl: string,
 *     requestedAt: \DateTimeImmutable,
 *     authorizedAt?: \DateTimeImmutable,
 *     failedAt?: \DateTimeImmutable,
 *     capturedAt?: \DateTimeImmutable,
 *     cancelledAt?: \DateTimeImmutable,
 *     refundInitiatedAt?: \DateTimeImmutable,
 *     refundConfirmedAt?: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateTestFactory<OrderPayment, Attributes>
 */
final class OrderPaymentTestFactory extends AbstractAggregateTestFactory
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(['orderId' => $orderId]);
    }

    public function withAmountInCents(int $amountInCents): self
    {
        return $this->withAttributes(['amount' => Money::fromCents($amountInCents)]);
    }

    public function withReference(string $reference): self
    {
        return $this->withAttributes(['reference' => PaymentReference::fromString($reference)]);
    }

    public function withCheckoutUrl(string $checkoutUrl): self
    {
        return $this->withAttributes(['checkoutUrl' => $checkoutUrl]);
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->withAttributes(['requestedAt' => $requestedAt]);
    }

    public function authorized(?\DateTimeImmutable $authorizedAt = null): self
    {
        $authorizedAt ??= Clock::get()->now();

        return $this->withAttributes(['authorizedAt' => $authorizedAt])
            ->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->authorize($authorizedAt));
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        $failedAt ??= Clock::get()->now();

        return $this->withAttributes(['failedAt' => $failedAt])
            ->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->fail($failedAt));
    }

    public function captured(?\DateTimeImmutable $capturedAt = null): self
    {
        $capturedAt ??= Clock::get()->now();

        return $this->withAttributes(['capturedAt' => $capturedAt])
            ->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->capture($capturedAt));
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $cancelledAt ??= Clock::get()->now();

        return $this->withAttributes(['cancelledAt' => $cancelledAt])
            ->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->cancel($cancelledAt));
    }

    public function refundInitiated(?\DateTimeImmutable $initiatedAt = null): self
    {
        $initiatedAt ??= Clock::get()->now();

        return $this->withAttributes(['refundInitiatedAt' => $initiatedAt])
            ->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->initiateRefund($initiatedAt));
    }

    public function refundConfirmed(?\DateTimeImmutable $refundedAt = null): self
    {
        $refundedAt ??= Clock::get()->now();

        return $this->withAttributes(['refundConfirmedAt' => $refundedAt])
            ->withModifier(static fn (OrderPayment $orderPayment) => $orderPayment->confirmRefund($refundedAt));
    }

    protected function defaults(): array
    {
        return [
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'amount' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
            'reference' => static fn (): PaymentReference => PaymentReference::fromString(SeededFaker::get()->regexify('GLBX-[A-Z0-9]{8}')),
            'checkoutUrl' => static fn (): string => 'https://checkout.globex.test/pay/'.SeededFaker::get()->regexify('[A-Z0-9]{8}'),
            'requestedAt' => ClockSequence::next(...),
        ];
    }

    protected function build(): OrderPayment
    {
        $orderId = $this->attribute('orderId');

        return OrderPayment::request(
            id: OrderPaymentId::forOrder($orderId),
            orderId: $orderId,
            amount: $this->attribute('amount'),
            reference: $this->attribute('reference'),
            checkoutUrl: $this->attribute('checkoutUrl'),
            requestedAt: $this->attribute('requestedAt'),
        );
    }
}
