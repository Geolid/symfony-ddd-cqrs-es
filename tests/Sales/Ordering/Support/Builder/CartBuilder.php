<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Support\Builder;

use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Cart\Cart;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Sales\Ordering\Domain\Shared\ValueObject\LineId;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: CartId,
 *     buyerId: string,
 *     startedAt: \DateTimeImmutable,
 *     product: Product,
 *     quantity: Quantity,
 *     addedAt: \DateTimeImmutable,
 *     removedAt: \DateTimeImmutable,
 *     changedAt: \DateTimeImmutable,
 *     checkedOutAt: \DateTimeImmutable,
 *     abandonedAt: \DateTimeImmutable,
 *     convertedAt: \DateTimeImmutable,
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

    public function withBuyerId(string $buyerId): self
    {
        return $this->withAttributes(buyerId: $buyerId);
    }

    public function withStartedAt(\DateTimeImmutable $startedAt): self
    {
        return $this->withAttributes(startedAt: $startedAt);
    }

    public function lineAdded(?Product $product = null, ?Quantity $quantity = null, ?\DateTimeImmutable $addedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['product' => $product, 'quantity' => $quantity, 'addedAt' => $addedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->addLine($builder['product'], $builder['quantity'], $builder['addedAt']),
        );
    }

    public function lineRemoved(?\DateTimeImmutable $removedAt = null): self
    {
        $builder = null !== $removedAt ? $this->withAttributes(removedAt: $removedAt) : $this;

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->removeLine($builder->lineId(), $builder['removedAt']),
        );
    }

    public function lineQuantityChanged(?Quantity $quantity = null, ?\DateTimeImmutable $changedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter(
            ['quantity' => $quantity, 'changedAt' => $changedAt],
            static fn (mixed $value): bool => null !== $value,
        ));

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->changeQuantity($builder->lineId(), $builder['quantity'], $builder['changedAt']),
        );
    }

    public function checkedOut(?\DateTimeImmutable $checkedOutAt = null): self
    {
        $builder = null !== $checkedOutAt ? $this->withAttributes(checkedOutAt: $checkedOutAt) : $this;

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->checkout($builder['checkedOutAt']),
        );
    }

    public function checkoutAbandoned(?\DateTimeImmutable $abandonedAt = null): self
    {
        $builder = null !== $abandonedAt ? $this->withAttributes(abandonedAt: $abandonedAt) : $this;

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->abandonCheckout($builder['abandonedAt']),
        );
    }

    public function converted(?\DateTimeImmutable $convertedAt = null): self
    {
        $builder = null !== $convertedAt ? $this->withAttributes(convertedAt: $convertedAt) : $this;

        return $builder->withModifier(
            static fn (Cart $cart, self $builder) => $cart->convert($builder['convertedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): CartId => CartId::fromString(Uuid::uuid7()->toString()),
            'buyerId' => static fn (): string => Uuid::uuid7()->toString(),
            'startedAt' => static fn (): \DateTimeImmutable => $now,
            'product' => static function (): Product {
                Assert::string($label = SeededFaker::get()->words(3, true));

                return Product::of(Uuid::uuid7()->toString(), Label::fromString($label), Money::fromCents(SeededFaker::get()->numberBetween(500, 5_000)));
            },
            'quantity' => static fn (): Quantity => Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
            'addedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 minute'),
            'removedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 minute'),
            'changedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 minute'),
            'checkedOutAt' => static fn (): \DateTimeImmutable => $now->modify('+3 minute'),
            'abandonedAt' => static fn (): \DateTimeImmutable => $now->modify('+4 minute'),
            'convertedAt' => static fn (): \DateTimeImmutable => $now->modify('+5 minute'),
        ];
    }

    protected function build(): Cart
    {
        return Cart::start(
            id: $this['id'],
            buyerId: $this['buyerId'],
            startedAt: $this['startedAt'],
        );
    }

    private function lineId(): LineId
    {
        return LineId::forProduct($this['id']->toString(), $this['product']->id);
    }
}
