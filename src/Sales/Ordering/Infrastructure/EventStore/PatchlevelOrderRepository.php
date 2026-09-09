<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelOrderRepository implements OrderRepositoryInterface
{
    /**
     * @param Repository<Order> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.ordering.order.repository')]
        private Repository $repository,
    ) {
    }

    public function has(OrderId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(OrderId $id): Order
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw OrderNotFoundException::forId($id->toString());
        }
    }

    public function save(Order $order): void
    {
        try {
            $this->repository->save($order);
        } catch (AggregateAlreadyExists) {
            throw OrderAlreadyExistsException::forId($order->id->toString());
        }
    }
}
