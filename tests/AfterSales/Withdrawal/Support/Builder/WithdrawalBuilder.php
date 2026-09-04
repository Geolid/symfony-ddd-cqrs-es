<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Support\Builder;

use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use AfterSales\Withdrawal\Domain\Withdrawal;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: WithdrawalId,
 *     orderId: string,
 *     customerId: string,
 *     actingCustomerId: string,
 *     shippingAddress: PostalAddress,
 *     deliveredAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     receivedAt: \DateTimeImmutable,
 *     approvedAt: \DateTimeImmutable,
 *     reason: string,
 *     rejectedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Withdrawal, Attributes>
 */
final class WithdrawalBuilder extends AbstractAggregateBuilder
{
    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(orderId: $orderId);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(customerId: $customerId);
    }

    public function withActingCustomerId(string $actingCustomerId): self
    {
        return $this->withAttributes(actingCustomerId: $actingCustomerId);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(shippingAddress: $shippingAddress);
    }

    public function withDeliveredAt(\DateTimeImmutable $deliveredAt): self
    {
        return $this->withAttributes(deliveredAt: $deliveredAt);
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->withAttributes(requestedAt: $requestedAt);
    }

    public function received(?\DateTimeImmutable $receivedAt = null): self
    {
        $builder = null !== $receivedAt ? $this->withAttributes(receivedAt: $receivedAt) : $this;

        return $builder->withModifier(
            static fn (Withdrawal $withdrawal, self $builder) => $withdrawal->receive($builder['receivedAt']),
        );
    }

    public function approved(?\DateTimeImmutable $approvedAt = null): self
    {
        $builder = null !== $approvedAt ? $this->withAttributes(approvedAt: $approvedAt) : $this;

        return $builder->withModifier(
            static fn (Withdrawal $withdrawal, self $builder) => $withdrawal->approve($builder['approvedAt']),
        );
    }

    public function rejected(
        ?string $reason = null,
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'reason' => $reason,
            'rejectedAt' => $rejectedAt,
        ]));

        return $builder->withModifier(
            static fn (Withdrawal $withdrawal, self $builder) => $withdrawal->reject($builder['reason'], $builder['rejectedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => static fn (?self $builder): WithdrawalId => WithdrawalId::forOrder(
                null !== $builder ? $builder['orderId'] : self::sample('orderId'),
            ),
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'actingCustomerId' => static fn (?self $builder): string => null !== $builder ? $builder['customerId'] : self::sample('customerId'),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'deliveredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('-3 days'),
            'requestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'receivedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'approvedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
            'reason' => static fn (): string => SeededFaker::get()->sentence(4),
            'rejectedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
        ];
    }

    protected function build(): Withdrawal
    {
        return Withdrawal::request(
            id: $this['id'],
            orderId: $this['orderId'],
            customerId: $this['customerId'],
            actingCustomerId: $this['actingCustomerId'],
            shippingAddress: $this['shippingAddress'],
            deliveredAt: $this['deliveredAt'],
            now: $this['requestedAt'],
        );
    }
}
