<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Builder;

use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Sales\Ordering\Domain\Order\ValueObject\Product;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\CountryCode;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\Quantity;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: OrderId,
 *     cartId: string,
 *     customerId: string,
 *     checkoutSessionId: string,
 *     shippingAddress: PostalAddress,
 *     items: list<OrderItem>,
 *     currency: Currency,
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
    public function withCartId(string $cartId): self
    {
        return $this->withAttributes(cartId: $cartId);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(customerId: $customerId);
    }

    public function withCheckoutSessionId(string $checkoutSessionId): self
    {
        return $this->withAttributes(checkoutSessionId: $checkoutSessionId);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(shippingAddress: $shippingAddress);
    }

    /**
     * @param list<OrderItem> $items
     */
    public function withItems(array $items): self
    {
        return $this->withAttributes(items: $items);
    }

    public function withCurrency(string $currency): self
    {
        return $this->withAttributes(currency: Currency::from($currency));
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
            static fn (Order $order, self $builder) => $order->cancel($builder['customerId'], $builder['cancelledAt']),
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
            'id' => static fn (?self $builder): OrderId => OrderId::forCheckoutSession(
                null !== $builder ? $builder['checkoutSessionId'] : self::sample('checkoutSessionId'),
            ),
            'cartId' => static fn (): string => Uuid::uuid7()->toString(),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'checkoutSessionId' => static fn (): string => Uuid::uuid7()->toString(),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), self::randomCountryCode()),
            ),
            'items' => static function (?self $builder): array {
                $currency = null !== $builder ? $builder['currency'] : self::sample('currency');

                return array_map(static function () use ($currency): OrderItem {
                    $product = Product::of(Uuid::uuid7()->toString(), Label::fromString(SeededFaker::get()->sentence(3)), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000), $currency->value));

                    return OrderItem::of(
                        $product,
                        Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
                        Money::fromCents(SeededFaker::get()->numberBetween(50, 500), $currency->value),
                    );
                }, range(1, SeededFaker::get()->numberBetween(1, 3)));
            },
            'currency' => static fn (): Currency => Currency::EUR,
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
            customerId: $this['customerId'],
            checkoutSessionId: $this['checkoutSessionId'],
            shippingAddress: $this['shippingAddress'],
            items: $this['items'],
            currency: $this['currency'],
            confirmedAt: $this['confirmedAt'],
        );
    }

    private static function randomCountryCode(): string
    {
        Assert::string($countryCode = SeededFaker::get()->randomElement(array_diff(CountryCode::values(), [CountryCode::ZZ->value])));

        return $countryCode;
    }
}
