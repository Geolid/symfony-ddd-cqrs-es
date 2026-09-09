<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Ordering\Domain\Cart\Cart;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelCartRepository implements CartRepositoryInterface
{
    /**
     * @param Repository<Cart> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.ordering.cart.repository')]
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
