<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\Repository;

use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Domain\Exception\AggregateNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OrderPaymentRepository implements OrderPaymentRepositoryInterface
{
    /**
     * @param Repository<OrderPayment> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.order.payment.repository')]
        private Repository $repository,
    ) {
    }

    public function has(OrderPaymentId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(OrderPaymentId $id): OrderPayment
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw AggregateNotFoundException::forId(OrderPayment::class, $id->toString());
        }
    }

    public function save(OrderPayment $orderPayment): void
    {
        $this->repository->save($orderPayment);
    }
}
