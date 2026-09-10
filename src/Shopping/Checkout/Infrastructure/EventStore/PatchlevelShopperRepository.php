<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Shopping\Checkout\Domain\Exception\ShopperAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\Shopper;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelShopperRepository implements ShopperRepositoryInterface
{
    /**
     * @param Repository<Shopper> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.shopping.checkout.shopper.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ShopperId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ShopperId $id): Shopper
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw ShopperNotFoundException::forId($id->toString());
        }
    }

    public function save(Shopper $shopper): void
    {
        try {
            $this->repository->save($shopper);
        } catch (AggregateAlreadyExists) {
            throw ShopperAlreadyExistsException::forId($shopper->id->toString());
        }
    }
}
