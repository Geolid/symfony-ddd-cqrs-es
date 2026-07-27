<?php

declare(strict_types=1);

namespace Ordering\Tests\Order\Support\Factory;

use Ordering\Order\Domain\Money;
use Ordering\Order\Domain\Order;
use Ordering\Order\Domain\OrderId;
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

    public function withTotalAmountInCents(int $totalAmountInCents): self
    {
        return static::new(array_merge($this->attributes, ['totalAmountInCents' => $totalAmountInCents]));
    }

    public function cancelled(): self
    {
        return $this->withModifier(static fn (Order $order) => $order->cancel(new \DateTimeImmutable('now', new \DateTimeZone('UTC'))));
    }

    protected function defaults(): array
    {
        return [
            'id' => OrderId::generate()->toString(),
            'customerId' => 'customer-'.self::faker()->numerify('###'),
            'totalAmountInCents' => self::faker()->numberBetween(500, 25_000),
            'placedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Order
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($customerId = $attributes['customerId']);
        Assert::natural($totalAmountInCents = $attributes['totalAmountInCents']);
        Assert::isInstanceOf($placedAt = $attributes['placedAt'], \DateTimeInterface::class);

        return Order::place(
            OrderId::fromString($id),
            $customerId,
            Money::fromCents($totalAmountInCents),
            \DateTimeImmutable::createFromInterface($placedAt),
        );
    }
}
