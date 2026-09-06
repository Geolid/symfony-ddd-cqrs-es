<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Support\Builder;

use Finance\Refund\Domain\Refund;
use Finance\Refund\Domain\ValueObject\RefundId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Money;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: RefundId,
 *     paymentId: string,
 *     orderId: string,
 *     amount: Money,
 *     initiatedAt: \DateTimeImmutable,
 *     refundedAt: \DateTimeImmutable,
 *     failedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Refund, Attributes>
 */
final class RefundBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: RefundId::fromString($id));
    }

    public function withPaymentId(string $paymentId): self
    {
        return $this->withAttributes(paymentId: $paymentId);
    }

    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(orderId: $orderId);
    }

    public function withAmountInCents(int $amountInCents): self
    {
        return $this->withAttributes(amount: Money::fromCents($amountInCents));
    }

    public function withInitiatedAt(\DateTimeImmutable $initiatedAt): self
    {
        return $this->withAttributes(initiatedAt: $initiatedAt);
    }

    public function confirmed(?\DateTimeImmutable $refundedAt = null): self
    {
        $builder = null !== $refundedAt ? $this->withAttributes(refundedAt: $refundedAt) : $this;

        return $builder->withModifier(
            static fn (Refund $refund, self $builder) => $refund->confirm($builder['refundedAt']),
        );
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        $builder = null !== $failedAt ? $this->withAttributes(failedAt: $failedAt) : $this;

        return $builder->withModifier(
            static fn (Refund $refund, self $builder) => $refund->fail($builder['failedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => RefundId::generate(...),
            'paymentId' => static fn (): string => Uuid::uuid7()->toString(),
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'amount' => static fn (): Money => Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
            'initiatedAt' => static fn (): \DateTimeImmutable => $now,
            'refundedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'failedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): Refund
    {
        return Refund::initiate(
            id: $this['id'],
            paymentId: $this['paymentId'],
            orderId: $this['orderId'],
            amount: $this['amount'],
            initiatedAt: $this['initiatedAt'],
        );
    }
}
