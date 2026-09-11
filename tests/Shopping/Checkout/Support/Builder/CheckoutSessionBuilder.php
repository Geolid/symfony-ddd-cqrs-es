<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Builder;

use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\Cart\Entity\Line;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: CheckoutSessionId,
 *     cartId: string,
 *     shopperId: string,
 *     lines: list<Line>,
 *     shippingAddress: PostalAddress,
 *     billingAddress: PostalAddress,
 *     totalAmountInCents: int,
 *     openedAt: \DateTimeImmutable,
 *     expiredAt: \DateTimeImmutable,
 *     staledAt: \DateTimeImmutable,
 *     consumedAt: \DateTimeImmutable,
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

    public function withShopperId(string $shopperId): self
    {
        return $this->withAttributes(shopperId: $shopperId);
    }

    /**
     * @param list<Line> $lines
     */
    public function withLines(array $lines): self
    {
        return $this->withAttributes(lines: $lines);
    }

    public function withShippingAddress(PostalAddress $shippingAddress): self
    {
        return $this->withAttributes(shippingAddress: $shippingAddress);
    }

    public function withBillingAddress(PostalAddress $billingAddress): self
    {
        return $this->withAttributes(billingAddress: $billingAddress);
    }

    public function withTotalAmountInCents(int $totalAmountInCents): self
    {
        return $this->withAttributes(totalAmountInCents: $totalAmountInCents);
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

    public function consumed(?\DateTimeImmutable $consumedAt = null): self
    {
        $builder = null !== $consumedAt ? $this->withAttributes(consumedAt: $consumedAt) : $this;

        return $builder->withModifier(
            static fn (CheckoutSession $checkoutSession, self $builder) => $checkoutSession->consume($builder['consumedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): CheckoutSessionId => CheckoutSessionId::fromString(Uuid::uuid7()->toString()),
            'cartId' => static fn (): string => Uuid::uuid7()->toString(),
            'shopperId' => static fn (): string => Uuid::uuid7()->toString(),
            'lines' => static fn (): array => array_map(static function (): Line {
                Assert::string($label = SeededFaker::get()->words(3, true));
                $productId = Uuid::uuid7()->toString();

                return new Line(
                    LineId::forProduct(Uuid::uuid7()->toString(), $productId),
                    Product::of($productId, Label::fromString($label), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000))),
                    Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
                );
            }, range(1, SeededFaker::get()->numberBetween(1, 3))),
            'shippingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'billingAddress' => static fn (): PostalAddress => PostalAddress::of(
                SeededFaker::get()->name(),
                Address::of(SeededFaker::get()->streetAddress(), SeededFaker::get()->postcode(), SeededFaker::get()->city(), SeededFaker::get()->countryCode()),
            ),
            'totalAmountInCents' => static fn (): int => SeededFaker::get()->numberBetween(500, 5_000),
            'openedAt' => static fn (): \DateTimeImmutable => $now,
            'expiredAt' => static fn (): \DateTimeImmutable => $now->modify('+30 minutes'),
            'staledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 minute'),
            'consumedAt' => static fn (): \DateTimeImmutable => $now->modify('+5 minutes'),
        ];
    }

    protected function build(): CheckoutSession
    {
        return CheckoutSession::open(
            id: $this['id'],
            cartId: $this['cartId'],
            shopperId: $this['shopperId'],
            lines: $this['lines'],
            shippingAddress: $this['shippingAddress'],
            billingAddress: $this['billingAddress'],
            totalAmountInCents: $this['totalAmountInCents'],
            openedAt: $this['openedAt'],
        );
    }
}
