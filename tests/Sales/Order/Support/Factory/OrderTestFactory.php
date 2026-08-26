<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Factory;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Service\RetentionWindow;
use Sales\Order\Domain\Service\ReturnWindow;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Symfony\Component\Clock\Clock;
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

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['shippingAddress' => $shippingAddress]));
    }

    public function withBillingAddress(PostalAddress $billingAddress): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['billingAddress' => $billingAddress]));
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

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        Assert::stringNotEmpty($customerId = $this->attributes['customerId'] ?? Uuid::uuid7()->toString());
        $cancelledAt ??= Clock::get()->now();

        return $this->withAttributes(array_merge($this->attributes, ['customerId' => $customerId]))
            ->withModifier(static fn (Order $order) => $order->cancel($customerId, $cancelledAt));
    }

    public function confirmed(?\DateTimeImmutable $confirmedAt = null): self
    {
        $confirmedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->confirm($confirmedAt));
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $dispatchedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->dispatch($dispatchedAt));
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        $deliveredAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->deliver($deliveredAt));
    }

    public function completed(
        ?\DateTimeImmutable $now = null,
        ReturnWindow $returnWindow = new ReturnWindow(14),
    ): self {
        $now ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->complete($now, $returnWindow));
    }

    public function returnRequested(
        ?\DateTimeImmutable $requestedAt = null,
        ReturnWindow $returnWindow = new ReturnWindow(14),
    ): self {
        Assert::stringNotEmpty($customerId = $this->attributes['customerId'] ?? Uuid::uuid7()->toString());
        $requestedAt ??= Clock::get()->now();

        return $this->withAttributes(array_merge($this->attributes, ['customerId' => $customerId]))
            ->withModifier(static fn (Order $order) => $order->requestReturn($customerId, $requestedAt, $returnWindow));
    }

    public function returned(?\DateTimeImmutable $returnedAt = null): self
    {
        $returnedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->confirmReturn($returnedAt));
    }

    public function returnRejected(
        string $reason = 'item damaged beyond resale',
        ?\DateTimeImmutable $rejectedAt = null,
    ): self {
        $rejectedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->rejectReturn($reason, $rejectedAt));
    }

    public function anonymized(
        ?\DateTimeImmutable $now = null,
        RetentionWindow $retentionWindow = new RetentionWindow(3650),
    ): self {
        $now ??= Clock::get()->now();

        return $this->withModifier(static fn (Order $order) => $order->anonymize($now, $retentionWindow));
    }

    protected function defaults(): array
    {
        return [
            'id' => OrderId::generate()->toString(),
            'customerId' => Uuid::uuid7()->toString(),
            'shippingAddress' => PostalAddress::of(
                FullName::of(self::faker()->firstName(), self::faker()->lastName()),
                Address::of(self::faker()->streetAddress(), self::faker()->postcode(), self::faker()->city()),
            ),
            'billingAddress' => PostalAddress::of(
                FullName::of(self::faker()->firstName(), self::faker()->lastName()),
                Address::of(self::faker()->streetAddress(), self::faker()->postcode(), self::faker()->city()),
            ),
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
        Assert::isInstanceOf($shippingAddress = $attributes['shippingAddress'], PostalAddress::class);
        Assert::isInstanceOf($billingAddress = $attributes['billingAddress'], PostalAddress::class);
        Assert::isList($lines = $attributes['lines']);
        Assert::allIsInstanceOf($lines, OrderLine::class);
        Assert::isInstanceOf($placedAt = $attributes['placedAt'], \DateTimeInterface::class);

        return Order::place(
            OrderId::fromString($id),
            $customerId,
            $shippingAddress,
            $billingAddress,
            $lines,
            \DateTimeImmutable::createFromInterface($placedAt),
        );
    }
}
