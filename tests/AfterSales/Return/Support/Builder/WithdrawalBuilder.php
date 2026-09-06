<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Support\Builder;

use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\Withdrawal;
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
 *     buyerId: string,
 *     actingBuyerId: string,
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
    public function withId(string $id): self
    {
        return $this->withAttributes(id: WithdrawalId::fromString($id));
    }

    public function withOrderId(string $orderId): self
    {
        return $this->withAttributes(orderId: $orderId);
    }

    public function withBuyerId(string $buyerId): self
    {
        return $this->withAttributes(buyerId: $buyerId);
    }

    public function withActingBuyerId(string $actingBuyerId): self
    {
        return $this->withAttributes(actingBuyerId: $actingBuyerId);
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
        $now = Clock::get()->now();

        return [
            'id' => WithdrawalId::generate(...),
            'orderId' => static fn (): string => Uuid::uuid7()->toString(),
            'buyerId' => static fn (): string => Uuid::uuid7()->toString(),
            'actingBuyerId' => static fn (?self $builder): string => null !== $builder ? $builder['buyerId'] : self::sample('buyerId'),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'deliveredAt' => static fn (): \DateTimeImmutable => $now->modify('-3 days'),
            'requestedAt' => static fn (): \DateTimeImmutable => $now,
            'receivedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'approvedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
            'reason' => static fn (): string => SeededFaker::get()->sentence(4),
            'rejectedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
        ];
    }

    protected function build(): Withdrawal
    {
        return Withdrawal::request(
            id: $this['id'],
            orderId: $this['orderId'],
            buyerId: $this['buyerId'],
            actingBuyerId: $this['actingBuyerId'],
            shippingAddress: $this['shippingAddress'],
            deliveredAt: $this['deliveredAt'],
            now: $this['requestedAt'],
        );
    }
}
