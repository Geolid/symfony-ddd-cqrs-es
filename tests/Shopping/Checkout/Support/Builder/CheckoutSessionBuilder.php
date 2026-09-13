<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Builder;

use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;
use Shopping\Checkout\Domain\ValueObject\Quantity;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: CheckoutSessionId,
 *     cartId: string,
 *     customerId: string,
 *     items: list<CheckoutItem>,
 *     shippingAddress: PostalAddress,
 *     billingAddress: PostalAddress,
 *     paymentId: string,
 *     openedAt: \DateTimeImmutable,
 *     expiredAt: \DateTimeImmutable,
 *     staledAt: \DateTimeImmutable,
 *     completedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<CheckoutSession, Attributes>
 */
final class CheckoutSessionBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: CheckoutSessionId::fromString($id));
    }

    public function withCartId(string $cartId): self
    {
        return $this->withAttributes(cartId: $cartId);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(customerId: $customerId);
    }

    /**
     * @param list<CheckoutItem> $items
     */
    public function withItems(array $items): self
    {
        return $this->withAttributes(items: $items);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(shippingAddress: $shippingAddress);
    }

    public function withBillingAddress(PostalAddress $billingAddress): self
    {
        return $this->withAttributes(billingAddress: $billingAddress);
    }

    public function withPaymentId(string $paymentId): self
    {
        return $this->withAttributes(paymentId: $paymentId);
    }

    public function withOpenedAt(\DateTimeImmutable $openedAt): self
    {
        return $this->withAttributes(openedAt: $openedAt);
    }

    public function expired(?\DateTimeImmutable $expiredAt = null): self
    {
        $builder = null !== $expiredAt ? $this->withAttributes(expiredAt: $expiredAt) : $this;

        return $builder->withModifier(
            static fn (CheckoutSession $checkoutSession, self $builder) => $checkoutSession->expire($builder['expiredAt']),
        );
    }

    public function staled(?\DateTimeImmutable $staledAt = null): self
    {
        $builder = null !== $staledAt ? $this->withAttributes(staledAt: $staledAt) : $this;

        return $builder->withModifier(
            static fn (CheckoutSession $checkoutSession, self $builder) => $checkoutSession->stale($builder['staledAt']),
        );
    }

    public function completed(?\DateTimeImmutable $completedAt = null): self
    {
        $builder = null !== $completedAt ? $this->withAttributes(completedAt: $completedAt) : $this;

        return $builder->withModifier(
            static fn (CheckoutSession $checkoutSession, self $builder) => $checkoutSession->complete(
                $builder['cartId'],
                $builder['customerId'],
                $builder['items'],
                $builder['shippingAddress'],
                $builder['billingAddress'],
                $builder['paymentId'],
                $builder['completedAt'],
            ),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): CheckoutSessionId => CheckoutSessionId::fromString(Uuid::uuid7()->toString()),
            'cartId' => static fn (): string => Uuid::uuid7()->toString(),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'items' => static fn (): array => array_map(
                static fn (): CheckoutItem => CheckoutItem::of(
                    Uuid::uuid7()->toString(),
                    Label::fromString(SeededFaker::get()->sentence(3)),
                    Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)),
                    Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
                ),
                range(1, SeededFaker::get()->numberBetween(1, 3)),
            ),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'billingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'paymentId' => static fn (): string => Uuid::uuid7()->toString(),
            'openedAt' => static fn (): \DateTimeImmutable => $now,
            'expiredAt' => static fn (): \DateTimeImmutable => $now->modify('+30 minutes'),
            'staledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 minute'),
            'completedAt' => static fn (): \DateTimeImmutable => $now->modify('+5 minutes'),
        ];
    }

    protected function build(): CheckoutSession
    {
        return CheckoutSession::open(
            id: $this['id'],
            cartId: $this['cartId'],
            customerId: $this['customerId'],
            items: $this['items'],
            shippingAddress: $this['shippingAddress'],
            billingAddress: $this['billingAddress'],
            openedAt: $this['openedAt'],
        );
    }
}
