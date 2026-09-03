<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Builder;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Domain\ValueObject\Money;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     orderId: string,
 *     amount: Money,
 *     reference: PaymentReference,
 *     checkoutUrl: string,
 *     requestedAt: \DateTimeImmutable,
 *     authorizedAt: \DateTimeImmutable,
 *     failedAt: \DateTimeImmutable,
 *     capturedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     refundInitiatedAt: \DateTimeImmutable,
 *     refundConfirmedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<OrderPayment, Attributes>
 */
final class OrderPaymentBuilder extends AbstractAggregateBuilder
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(orderId: $orderId);
    }

    public function withAmountInCents(int $amountInCents): self
    {
        return $this->withAttributes(amount: Money::fromCents($amountInCents));
    }

    public function withReference(string $reference): self
    {
        return $this->withAttributes(reference: PaymentReference::fromString($reference));
    }

    public function withCheckoutUrl(string $checkoutUrl): self
    {
        return $this->withAttributes(checkoutUrl: $checkoutUrl);
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->withAttributes(requestedAt: $requestedAt);
    }

    public function authorized(?\DateTimeImmutable $authorizedAt = null): self
    {
        $builder = null !== $authorizedAt ? $this->withAttributes(authorizedAt: $authorizedAt) : $this;

        return $builder->withModifier(
            static fn (OrderPayment $orderPayment, self $builder) => $orderPayment->authorize($builder['authorizedAt']),
        );
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        $builder = null !== $failedAt ? $this->withAttributes(failedAt: $failedAt) : $this;

        return $builder->withModifier(
            static fn (OrderPayment $orderPayment, self $builder) => $orderPayment->fail($builder['failedAt']),
        );
    }

    public function captured(?\DateTimeImmutable $capturedAt = null): self
    {
        $builder = null !== $capturedAt ? $this->withAttributes(capturedAt: $capturedAt) : $this;

        return $builder->withModifier(
            static fn (OrderPayment $orderPayment, self $builder) => $orderPayment->capture($builder['capturedAt']),
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (OrderPayment $orderPayment, self $builder) => $orderPayment->cancel($builder['cancelledAt']),
        );
    }

    public function refundInitiated(?\DateTimeImmutable $refundInitiatedAt = null): self
    {
        $builder = null !== $refundInitiatedAt ? $this->withAttributes(refundInitiatedAt: $refundInitiatedAt) : $this;

        return $builder->withModifier(
            static fn (OrderPayment $orderPayment, self $builder) => $orderPayment->initiateRefund($builder['refundInitiatedAt']),
        );
    }

    public function refundConfirmed(?\DateTimeImmutable $refundConfirmedAt = null): self
    {
        $builder = null !== $refundConfirmedAt ? $this->withAttributes(refundConfirmedAt: $refundConfirmedAt) : $this;

        return $builder->withModifier(
            static fn (OrderPayment $orderPayment, self $builder) => $orderPayment->confirmRefund($builder['refundConfirmedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'amount' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
            'reference' => static fn (): PaymentReference => PaymentReference::fromString(SeededFaker::get()->unique()->regexify('GLBX-[A-Z0-9]{8}')),
            'checkoutUrl' => static fn (): string => 'https://checkout.globex.test/pay/'.SeededFaker::get()->regexify('[A-Z0-9]{8}'),
            'requestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'authorizedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'failedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'capturedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'refundInitiatedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+3 day'),
            'refundConfirmedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+4 day'),
        ];
    }

    protected function build(): OrderPayment
    {
        $orderId = $this['orderId'];

        return OrderPayment::request(
            id: OrderPaymentId::forOrder($orderId),
            orderId: $orderId,
            amount: $this['amount'],
            reference: $this['reference'],
            checkoutUrl: $this['checkoutUrl'],
            requestedAt: $this['requestedAt'],
        );
    }
}
