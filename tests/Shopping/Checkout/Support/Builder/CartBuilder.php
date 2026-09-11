<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Builder;

use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: CartId,
 *     shopperId: string,
 *     startedAt: \DateTimeImmutable,
 *     product: Product,
 *     quantity: Quantity,
 *     lines: list<array{product: Product, quantity: Quantity}>,
 *     addedAt: \DateTimeImmutable,
 *     removedAt: \DateTimeImmutable,
 *     changedAt: \DateTimeImmutable,
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

    public function withShopperId(string $shopperId): self
    {
        return $this->withAttributes(shopperId: $shopperId);
    }

    public function withStartedAt(\DateTimeImmutable $startedAt): self
    {
        return $this->withAttributes(startedAt: $startedAt);
    }

    public function lineAdded(?Product $product = null, ?Quantity $quantity = null, ?\DateTimeImmutable $addedAt = null): self
    {
        $builder = null !== $addedAt ? $this->withAttributes(addedAt: $addedAt) : $this;

        $product ??= $builder['product'];
        $quantity ??= $builder['quantity'];
        $builder = $builder->withAttributes(product: $product, quantity: $quantity, lines: [...$builder['lines'], ['product' => $product, 'quantity' => $quantity]]);

        return $builder->withModifier(
            static fn (Cart $cart, self $modifierBuilder) => $cart->addLine($product, $quantity, $modifierBuilder['addedAt']),
        );
    }

    public function lineRemoved(?\DateTimeImmutable $removedAt = null): self
    {
        $builder = null !== $removedAt ? $this->withAttributes(removedAt: $removedAt) : $this;
        $index = array_key_last($builder['lines']);
        Assert::notNull($index);
        $productId = $builder['lines'][$index]['product']->id;

        return $builder->withModifier(
            static fn (Cart $cart, self $modifierBuilder) => $cart->removeLine(
                LineId::forProduct($modifierBuilder['id']->toString(), $productId),
                $modifierBuilder['removedAt'],
            ),
        );
    }

    public function lineQuantityChanged(?Quantity $quantity = null, ?\DateTimeImmutable $changedAt = null): self
    {
        $builder = null !== $changedAt ? $this->withAttributes(changedAt: $changedAt) : $this;
        $index = array_key_last($builder['lines']);
        Assert::notNull($index);
        $productId = $builder['lines'][$index]['product']->id;
        $quantity ??= $builder['quantity'];

        return $builder->withModifier(
            static fn (Cart $cart, self $modifierBuilder) => $cart->changeQuantity(
                LineId::forProduct($modifierBuilder['id']->toString(), $productId),
                $quantity,
                $modifierBuilder['changedAt'],
            ),
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
            'shopperId' => static fn (): string => Uuid::uuid7()->toString(),
            'startedAt' => static fn (): \DateTimeImmutable => $now,
            'product' => static function (): Product {
                Assert::string($label = SeededFaker::get()->words(3, true));

                return Product::of(Uuid::uuid7()->toString(), Label::fromString($label), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)));
            },
            'quantity' => static fn (): Quantity => Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
            'lines' => static fn (): array => [],
            'addedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 minute'),
            'removedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 minute'),
            'changedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 minute'),
            'purchasedAt' => static fn (): \DateTimeImmutable => $now->modify('+3 minute'),
        ];
    }

    protected function build(): Cart
    {
        return Cart::start(
            id: $this['id'],
            shopperId: $this['shopperId'],
            startedAt: $this['startedAt'],
        );
    }
}
