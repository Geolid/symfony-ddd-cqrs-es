<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Order>
 */
final class OrderTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['id' => $id]));
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['customerId' => $customerId]));
    }

    public function withBuyerAddress(string $buyerAddress): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['buyerAddress' => $buyerAddress]));
    }

    /**
     * @param list<OrderLine> $lines
     */
    public function withLines(array $lines): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['lines' => $lines]));
    }

    public function withTotalAmountInCents(int $totalAmountInCents): self
    {
        return $this->withLines([OrderLine::of(
            Product::of(Uuid::uuid7()->toString(), Label::fromString('Assorted goods'), Money::fromCents($totalAmountInCents)),
            1,
        )]);
    }

    public function withPlacedAt(\DateTimeImmutable $placedAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['placedAt' => $placedAt]));
    }

    public function cancelled(\DateTimeImmutable $cancelledAt = new \DateTimeImmutable('now +00:00')): self
    {
        Assert::stringNotEmpty($customerId = $this->attributes['customerId'] ?? Uuid::uuid7()->toString());

        return $this->withAttributes(array_merge($this->attributes, ['customerId' => $customerId]))
            ->withModifier(static fn (Order $order) => $order->cancel($customerId, $cancelledAt));
    }

    protected function defaults(): array
    {
        return [
            'id' => OrderId::generate()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'buyerAddress' => self::faker()->safeEmail(),
            'lines' => [OrderLine::of(
                Product::of(Uuid::uuid7()->toString(), Label::fromString(self::faker()->sentence(3)), Money::fromCents(self::faker()->numberBetween(500, 5_000))),
                self::faker()->numberBetween(1, 5),
            )],
            'placedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Order
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::stringNotEmpty($buyerAddress = $attributes['buyerAddress']);
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
