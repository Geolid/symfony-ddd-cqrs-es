<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Support\Builder;

use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: OrderId,
 *     buyerId: string,
 *     shippingAddress: PostalAddress,
 *     billingAddress: PostalAddress,
 *     lines: list<OrderLine>,
 *     placedAt: \DateTimeImmutable,
 *     confirmedAt: \DateTimeImmutable,
 *     preparedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     abortedAt: \DateTimeImmutable,
 *     dispatchedAt: \DateTimeImmutable,
 *     deliveredAt: \DateTimeImmutable,
 *     returnRequestedAt: \DateTimeImmutable,
 *     returnedAt: \DateTimeImmutable,
 *     disputedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Order, Attributes>
 */
final class OrderBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: OrderId::fromString($id));
    }

    public function withBuyerId(string $buyerId): self
    {
        return $this->withAttributes(buyerId: $buyerId);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(shippingAddress: $shippingAddress);
    }

    public function withBillingAddress(PostalAddress $billingAddress): self
    {
        return $this->withAttributes(billingAddress: $billingAddress);
    }

    /**
     * @param list<OrderLine> $lines
     */
    public function withLines(array $lines): self
    {
        return $this->withAttributes(lines: $lines);
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
        return $this->withAttributes(placedAt: $placedAt);
    }

    public function confirmed(?\DateTimeImmutable $confirmedAt = null): self
    {
        $builder = null !== $confirmedAt ? $this->withAttributes(confirmedAt: $confirmedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->confirm($builder['confirmedAt']),
        );
    }

    public function prepared(?\DateTimeImmutable $preparedAt = null): self
    {
        $builder = null !== $preparedAt ? $this->withAttributes(preparedAt: $preparedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->prepare($builder['preparedAt']),
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->cancel($builder['buyerId'], $builder['cancelledAt']),
        );
    }

    public function aborted(?\DateTimeImmutable $abortedAt = null): self
    {
        $builder = null !== $abortedAt ? $this->withAttributes(abortedAt: $abortedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->abort($builder['abortedAt']),
        );
    }

    public function dispatched(?\DateTimeImmutable $dispatchedAt = null): self
    {
        $builder = null !== $dispatchedAt ? $this->withAttributes(dispatchedAt: $dispatchedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->dispatch($builder['dispatchedAt']),
        );
    }

    public function delivered(?\DateTimeImmutable $deliveredAt = null): self
    {
        $builder = null !== $deliveredAt ? $this->withAttributes(deliveredAt: $deliveredAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->deliver($builder['deliveredAt']),
        );
    }

    public function returnRequested(?\DateTimeImmutable $returnRequestedAt = null): self
    {
        $builder = null !== $returnRequestedAt ? $this->withAttributes(returnRequestedAt: $returnRequestedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->requestReturn($builder['returnRequestedAt']),
        );
    }

    public function returned(?\DateTimeImmutable $returnedAt = null): self
    {
        $builder = null !== $returnedAt ? $this->withAttributes(returnedAt: $returnedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->return($builder['returnedAt']),
        );
    }

    public function disputed(?\DateTimeImmutable $disputedAt = null): self
    {
        $builder = null !== $disputedAt ? $this->withAttributes(disputedAt: $disputedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->dispute($builder['disputedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => OrderId::generate(...),
            'buyerId' => static fn (): string => Uuid::uuid7()->toString(),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'billingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'lines' => static fn (): array => [OrderLine::of(
                Product::of(Uuid::uuid7()->toString(), Label::fromString(SeededFaker::get()->sentence(3)), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000))),
                SeededFaker::get()->numberBetween(1, 5),
            )],
            'placedAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'confirmedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'preparedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'abortedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'dispatchedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+3 day'),
            'deliveredAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+4 day'),
            'returnRequestedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+5 day'),
            'returnedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+6 day'),
            'disputedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+6 day'),
        ];
    }

    protected function build(): Order
    {
        return Order::place(
            id: $this['id'],
            buyerId: $this['buyerId'],
            shippingAddress: $this['shippingAddress'],
            billingAddress: $this['billingAddress'],
            lines: $this['lines'],
            placedAt: $this['placedAt'],
        );
    }
}
