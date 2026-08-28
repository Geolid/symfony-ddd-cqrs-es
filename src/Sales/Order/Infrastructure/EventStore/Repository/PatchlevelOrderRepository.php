<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\EventStore\Repository;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Order\Domain\Exception\OrderAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelOrderRepository implements OrderRepositoryInterface
{
    /**
     * @param Repository<Order> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.order.order.repository')]
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
