<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelCartRepository implements CartRepositoryInterface
{
    /**
     * @param Repository<Cart> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.shopping.checkout.cart.repository')]
        private Repository $repository,
    ) {
    }

    public function has(CartId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(CartId $id): Cart
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw CartNotFoundException::forId($id->toString());
        }
    }

    public function save(Cart $cart): void
    {
        try {
            $this->repository->save($cart);
        } catch (AggregateAlreadyExists) {
            throw CartAlreadyExistsException::forId($cart->id->toString());
        }
    }
}
