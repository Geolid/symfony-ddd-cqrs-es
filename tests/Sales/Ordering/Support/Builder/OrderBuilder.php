<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Builder;

use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Entity\Line;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\ValueObject\LineId;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Sales\Ordering\Domain\Order\ValueObject\Quantity;
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
 *     cartId: string,
 *     shopperId: string,
 *     paymentId: string,
 *     shippingAddress: PostalAddress,
 *     billingAddress: PostalAddress,
 *     lines: list<Line>,
 *     confirmedAt: \DateTimeImmutable,
 *     preparedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     failedAt: \DateTimeImmutable,
 *     dispatchedAt: \DateTimeImmutable,
 *     deliveredAt: \DateTimeImmutable,
 *     erasureApprovedAt: \DateTimeImmutable,
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

    public function withCartId(string $cartId): self
    {
        return $this->withAttributes(cartId: $cartId);
    }

    public function withShopperId(string $shopperId): self
    {
        return $this->withAttributes(shopperId: $shopperId);
    }

    public function withPaymentId(string $paymentId): self
    {
        return $this->withAttributes(paymentId: $paymentId);
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
     * @param list<Line> $lines
     */
    public function withLines(array $lines): self
    {
        return $this->withAttributes(lines: $lines);
    }

    public function withConfirmedAt(\DateTimeImmutable $confirmedAt): self
    {
        return $this->withAttributes(confirmedAt: $confirmedAt);
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
            static fn (Order $order, self $builder) => $order->cancel($builder['shopperId'], $builder['cancelledAt']),
        );
    }

    public function failed(?\DateTimeImmutable $failedAt = null): self
    {
        $builder = null !== $failedAt ? $this->withAttributes(failedAt: $failedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->fail($builder['failedAt']),
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

    public function erasureApproved(?\DateTimeImmutable $erasureApprovedAt = null): self
    {
        $builder = null !== $erasureApprovedAt ? $this->withAttributes(erasureApprovedAt: $erasureApprovedAt) : $this;

        return $builder->withModifier(
            static fn (Order $order, self $builder) => $order->approveErasure($builder['erasureApprovedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): OrderId => OrderId::fromString(Uuid::uuid7()->toString()),
            'cartId' => static fn (): string => Uuid::uuid7()->toString(),
            'shopperId' => static fn (): string => Uuid::uuid7()->toString(),
            'paymentId' => static fn (): string => Uuid::uuid7()->toString(),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'billingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'lines' => static fn (): array => array_map(static function (): Line {
                $product = Product::of(Uuid::uuid7()->toString(), Label::fromString(SeededFaker::get()->sentence(3)), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)));

                return new Line(LineId::forProduct(Uuid::uuid7()->toString(), $product->id), $product, Quantity::of(SeededFaker::get()->numberBetween(1, 5)));
            }, range(1, SeededFaker::get()->numberBetween(1, 3))),
            'confirmedAt' => static fn (): \DateTimeImmutable => $now,
            'preparedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'failedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'dispatchedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
            'deliveredAt' => static fn (): \DateTimeImmutable => $now->modify('+3 day'),
            'erasureApprovedAt' => static fn (): \DateTimeImmutable => $now->modify('+4 day'),
        ];
    }

    protected function build(): Order
    {
        return Order::confirm(
            id: $this['id'],
            cartId: $this['cartId'],
            shopperId: $this['shopperId'],
            paymentId: $this['paymentId'],
            shippingAddress: $this['shippingAddress'],
            billingAddress: $this['billingAddress'],
            lines: $this['lines'],
            confirmedAt: $this['confirmedAt'],
        );
    }
}
