<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Support\Builder;

use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Ramsey\Uuid\Uuid;
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
 * }
 *
 * @extends AbstractAggregateBuilder<Payment, Attributes>
 */
final class PaymentBuilder extends AbstractAggregateBuilder
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
            static fn (Payment $orderPayment, self $builder) => $orderPayment->authorize($builder['authorizedAt']),
        );
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        $builder = null !== $failedAt ? $this->withAttributes(failedAt: $failedAt) : $this;

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->fail($builder['failedAt']),
        );
    }

    public function captured(?\DateTimeImmutable $capturedAt = null): self
    {
        $builder = null !== $capturedAt ? $this->withAttributes(capturedAt: $capturedAt) : $this;

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->capture($builder['capturedAt']),
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->cancel($builder['cancelledAt']),
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
        ];
    }

    protected function build(): Payment
    {
        $orderId = $this['orderId'];

        return Payment::request(
            id: PaymentId::forOrder($orderId),
            orderId: $orderId,
            amount: $this['amount'],
            reference: $this['reference'],
            checkoutUrl: $this['checkoutUrl'],
            requestedAt: $this['requestedAt'],
        );
    }
}
