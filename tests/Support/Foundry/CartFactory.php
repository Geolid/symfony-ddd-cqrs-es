<?php

declare(strict_types=1);

namespace Support\Foundry;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Quantity;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\ValueObject\CartId;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * SPIKE — throwaway.
 *
 * @extends AbstractAggregateFactory<Cart, array<string, mixed>>
 */
final class CartFactory extends AbstractAggregateFactory
{
    /** @var list<string> factory-local state, carried over by Foundry's clone-per-call */
    private array $addedProductIds = [];

    public static function class(): string
    {
        return Cart::class;
    }

    public function productAdded(?string $productId = null, ?Quantity $quantity = null): self
    {
        $productId ??= Uuid::uuid7()->toString();
        $quantity ??= Quantity::of(faker()->numberBetween(1, 5));

        $factory = clone $this;
        $factory->addedProductIds[] = $productId;

        return $factory->afterInstantiate(
            static fn (Cart $cart) => $cart->addProduct($productId, $quantity, Clock::get()->now()->modify('+1 minute')),
        );
    }

    public function productRemoved(?string $productId = null): self
    {
        $productId ??= array_last($this->addedProductIds);
        Assert::string($productId);

        return $this->afterInstantiate(
            static fn (Cart $cart) => $cart->removeProduct($productId, Clock::get()->now()->modify('+2 minutes')),
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Cart::start(id: $parameters['id'], customerId: $parameters['customerId'], startedAt: $parameters['startedAt']);
    }

    protected function defaults(): array
    {
        return [
            'id' => CartId::fromString(Uuid::uuid7()->toString()),
            'customerId' => Uuid::uuid7()->toString(),
            'startedAt' => Clock::get()->now(),
        ];
    }
}
