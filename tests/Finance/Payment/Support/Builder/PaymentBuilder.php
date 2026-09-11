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
 *     id: PaymentId,
 *     checkoutSessionId: string,
 *     orderId: string,
 *     amount: Money,
 *     reference: PaymentReference,
 *     checkoutUrl: string,
 *     requestedAt: \DateTimeImmutable,
 *     authorizedAt: \DateTimeImmutable,
 *     failedAt: \DateTimeImmutable,
 *     capturedAt: \DateTimeImmutable,
 *     abandonedAt: \DateTimeImmutable,
 *     voidedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Payment, Attributes>
 */
final class PaymentBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: PaymentId::fromString($id));
    }

    public function withCheckoutSessionId(string $checkoutSessionId): self
    {
        return $this->withAttributes(checkoutSessionId: $checkoutSessionId);
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

    public function failed(?string $orderId = null, ?\DateTimeImmutable $failedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['orderId' => $orderId, 'failedAt' => $failedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->fail($builder['orderId'], $builder['failedAt']),
        );
    }

    public function captured(?string $orderId = null, ?\DateTimeImmutable $capturedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['orderId' => $orderId, 'capturedAt' => $capturedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->capture($builder['orderId'], $builder['capturedAt']),
        );
    }

    public function abandoned(?\DateTimeImmutable $abandonedAt = null): self
    {
        $builder = null !== $abandonedAt ? $this->withAttributes(abandonedAt: $abandonedAt) : $this;

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->abandon($builder['abandonedAt']),
        );
    }

    public function voided(?\DateTimeImmutable $voidedAt = null): self
    {
        $builder = null !== $voidedAt ? $this->withAttributes(voidedAt: $voidedAt) : $this;

        return $builder->withModifier(
            static fn (Payment $orderPayment, self $builder) => $orderPayment->void($builder['voidedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): PaymentId => PaymentId::fromString(Uuid::uuid7()->toString()),
            'checkoutSessionId' => static fn (): string => Uuid::uuid7()->toString(),
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'amount' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
            'reference' => static fn (): PaymentReference => PaymentReference::fromString(SeededFaker::get()->unique()->regexify('GLBX-[A-Z0-9]{8}')),
            'checkoutUrl' => static fn (): string => 'https://checkout.globex.test/pay/'.SeededFaker::get()->regexify('[A-Z0-9]{8}'),
            'requestedAt' => static fn (): \DateTimeImmutable => $now,
            'authorizedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'failedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'capturedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
            'abandonedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'voidedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): Payment
    {
        return Payment::request(
            id: $this['id'],
            checkoutSessionId: $this['checkoutSessionId'],
            amount: $this['amount'],
            reference: $this['reference'],
            checkoutUrl: $this['checkoutUrl'],
            requestedAt: $this['requestedAt'],
        );
    }
}
