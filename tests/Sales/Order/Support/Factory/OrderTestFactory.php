<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Money;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\OrderId;
use Sales\Order\Domain\OrderLine;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Order>
 */
final class OrderTestFactory extends AbstractAggregateTestFactory
{
    public function withCustomerId(string $customerId): self
    {
        return static::new(array_merge($this->attributes, ['customerId' => $customerId]));
    }

    public function withBuyerAddress(?string $buyerAddress): self
    {
        return static::new(array_merge($this->attributes, ['buyerAddress' => $buyerAddress]));
    }

    /**
     * @param list<OrderLine> $lines
     */
    public function withLines(array $lines): self
    {
        return static::new(array_merge($this->attributes, ['lines' => $lines]));
    }

    public function withTotalAmountInCents(int $totalAmountInCents): self
    {
        return $this->withLines([OrderLine::of('Assorted goods', 1, Money::fromCents($totalAmountInCents))]);
    }

    public function placedAt(\DateTimeImmutable $placedAt): self
    {
        return static::new(array_merge($this->attributes, ['placedAt' => $placedAt]));
    }

    public function cancelled(): self
    {
        return $this->withModifier(static fn (Order $order) => $order->cancel(new \DateTimeImmutable('now +00:00')));
    }

    protected function defaults(): array
    {
        return [
            'id' => OrderId::generate()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'buyerAddress' => self::faker()->safeEmail(),
            'lines' => [OrderLine::of(
                self::faker()->sentence(3),
                self::faker()->numberBetween(1, 5),
                Money::fromCents(self::faker()->numberBetween(500, 5_000)),
            )],
            'placedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Order
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::nullOrStringNotEmpty($buyerAddress = $attributes['buyerAddress']);
        Assert::isList($lines = $attributes['lines']);
        Assert::allIsInstanceOf($lines, OrderLine::class);
        Assert::isInstanceOf($placedAt = $attributes['placedAt'], \DateTimeInterface::class);

        return Order::place(
            OrderId::fromString($id),
            $customerId,
            $buyerAddress,
            $lines,
            \DateTimeImmutable::createFromInterface($placedAt),
        );
    }
}
