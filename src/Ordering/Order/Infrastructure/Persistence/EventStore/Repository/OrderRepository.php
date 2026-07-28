<?php

declare(strict_types=1);

namespace Ordering\Order\Infrastructure\Persistence\EventStore\Repository;

use Ordering\Order\Domain\Exception\OrderNotFoundException;
use Ordering\Order\Domain\Order;
use Ordering\Order\Domain\OrderId;
use Ordering\Order\Domain\Repository\OrderRepositoryInterface;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @param Repository<Order> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.ordering.order.repository')]
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
            throw OrderNotFoundException::forId($id);
        }
    }

    public function save(Order $order): void
    {
        $this->repository->save($order);
    }
}
