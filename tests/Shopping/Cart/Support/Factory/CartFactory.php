<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Support\Factory;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Quantity;
use Shared\Tests\Support\Factory\QuantityFactory;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\ValueObject\CartId;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Inputs = array{
 *     id: CartId,
 *     customerId: string,
 *     startedAt: \DateTimeImmutable,
 *     purchasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<Cart, Inputs>
 */
final class CartFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Cart::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => CartId::fromString($id)]);
    }

    public function withCustomerId(string $customerId): self
    {
        return $this->with(['customerId' => $customerId]);
    }

    public function withStartedAt(\DateTimeImmutable $startedAt): self
    {
        return $this->with(['startedAt' => $startedAt]);
    }

    public function productAdded(?string $productId = null, ?Quantity $quantity = null, ?\DateTimeImmutable $addedAt = null): self
    {
        $productId ??= Uuid::uuid7()->toString();
        $quantity ??= QuantityFactory::new()->create();
        $addedAt ??= Clock::get()->now()->modify('+1 minute');

        return $this->transition(
            static function (Cart $cart) use ($productId, $quantity, $addedAt): void {
                $cart->addProduct($productId, $quantity, $addedAt);
            },
        );
    }

    public function productRemoved(?string $productId = null, ?\DateTimeImmutable $removedAt = null): self
    {
        $removedAt ??= Clock::get()->now()->modify('+2 minutes');

        return $this->transition(
            static function (Cart $cart) use ($productId, $removedAt): void {
                $cart->removeProduct($productId ?? self::lastProductId($cart), $removedAt);
            },
        );
    }

    public function productQuantityChanged(?string $productId = null, ?Quantity $quantity = null, ?\DateTimeImmutable $changedAt = null): self
    {
        $quantity ??= QuantityFactory::new()->create();
        $changedAt ??= Clock::get()->now()->modify('+2 minutes');

        return $this->transition(
            static function (Cart $cart) use ($productId, $quantity, $changedAt): void {
                $cart->changeQuantity($productId ?? self::lastProductId($cart), $quantity, $changedAt);
            },
        );
    }

    public function purchased(?\DateTimeImmutable $purchasedAt = null): self
    {
        return $this->with(array_filter(['purchasedAt' => $purchasedAt]))->transition(
            static function (Cart $cart, array $inputs): void {
                $cart->purchase($inputs['purchasedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Cart::start(
            id: $parameters['id'],
            customerId: $parameters['customerId'],
            startedAt: $parameters['startedAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => CartIdFactory::new(),
            'customerId' => Uuid::uuid7()->toString(),
            'startedAt' => $now,
            'purchasedAt' => $now->modify('+3 minute'),
        ];
    }

    private static function lastProductId(Cart $cart): string
    {
        $productId = array_key_last($cart->products);
        Assert::string($productId);

        return $productId;
    }
}
