<?php

declare(strict_types=1);

namespace Sales\Buyer\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelBuyerRepository implements BuyerRepositoryInterface
{
    /**
     * @param Repository<Buyer> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.buyer.buyer.repository')]
        private Repository $repository,
    ) {
    }

    public function has(BuyerId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(BuyerId $id): Buyer
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw BuyerNotFoundException::forId($id->toString());
        }
    }

    public function save(Buyer $buyer): void
    {
        try {
            $this->repository->save($buyer);
        } catch (AggregateAlreadyExists) {
            throw BuyerAlreadyExistsException::forId($buyer->id->toString());
        }
    }
}
