<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Builder;

use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: CartId,
 *     customerId: string,
 *     startedAt: \DateTimeImmutable,
 *     productAdditions: list<array{productId: string, quantity: Quantity, addedAt: \DateTimeImmutable}>,
 *     productRemovals: list<array{productId: string, removedAt: \DateTimeImmutable}>,
 *     productQuantityChanges: list<array{productId: string, quantity: Quantity, changedAt: \DateTimeImmutable}>,
 *     purchasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Cart, Attributes>
 */
final class CartBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: CartId::fromString($id));
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->withAttributes(customerId: $customerId);
    }

    public function withStartedAt(\DateTimeImmutable $startedAt): self
    {
        return $this->withAttributes(startedAt: $startedAt);
    }

    public function productAdded(?string $productId = null, ?Quantity $quantity = null, ?\DateTimeImmutable $addedAt = null): self
    {
        $productId ??= Uuid::uuid7()->toString();
        $quantity ??= Quantity::of(SeededFaker::get()->numberBetween(1, 5));
        $addedAt ??= Clock::get()->now()->modify('+1 minute');

        $builder = $this->withAttributes(productAdditions: [...$this['productAdditions'], [
            'productId' => $productId,
            'quantity' => $quantity,
            'addedAt' => $addedAt,
        ]]);

        return $builder->withModifier(
            static fn (Cart $cart) => $cart->addProduct($productId, $quantity, $addedAt),
        );
    }

    public function productRemoved(?string $productId = null, ?\DateTimeImmutable $removedAt = null): self
    {
        $productId ??= $this->lastAddedProductId();
        $removedAt ??= Clock::get()->now()->modify('+2 minutes');

        $builder = $this->withAttributes(productRemovals: [...$this['productRemovals'], [
            'productId' => $productId,
            'removedAt' => $removedAt,
        ]]);

        return $builder->withModifier(
            static fn (Cart $cart) => $cart->removeProduct($productId, $removedAt),
        );
    }

    public function productQuantityChanged(?string $productId = null, ?Quantity $quantity = null, ?\DateTimeImmutable $changedAt = null): self
    {
        $productId ??= $this->lastAddedProductId();
        $quantity ??= Quantity::of(SeededFaker::get()->numberBetween(1, 5));
        $changedAt ??= Clock::get()->now()->modify('+2 minutes');

        $builder = $this->withAttributes(productQuantityChanges: [...$this['productQuantityChanges'], [
            'productId' => $productId,
            'quantity' => $quantity,
            'changedAt' => $changedAt,
        ]]);

        return $builder->withModifier(
            static fn (Cart $cart) => $cart->changeQuantity($productId, $quantity, $changedAt),
        );
    }

    public function purchased(?\DateTimeImmutable $purchasedAt = null): self
    {
        $builder = null !== $purchasedAt ? $this->withAttributes(purchasedAt: $purchasedAt) : $this;

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->purchase($builder['purchasedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): CartId => CartId::fromString(Uuid::uuid7()->toString()),
            'customerId' => static fn (): string => Uuid::uuid7()->toString(),
            'startedAt' => static fn (): \DateTimeImmutable => $now,
            'productAdditions' => static fn (): array => [],
            'productRemovals' => static fn (): array => [],
            'productQuantityChanges' => static fn (): array => [],
            'purchasedAt' => static fn (): \DateTimeImmutable => $now->modify('+3 minute'),
        ];
    }

    protected function build(): Cart
    {
        return Cart::start(
            id: $this['id'],
            customerId: $this['customerId'],
            startedAt: $this['startedAt'],
        );
    }

    private function lastAddedProductId(): string
    {
        $lastIndex = array_key_last($this['productAdditions']);
        Assert::notNull($lastIndex);

        return $this['productAdditions'][$lastIndex]['productId'];
    }
}
