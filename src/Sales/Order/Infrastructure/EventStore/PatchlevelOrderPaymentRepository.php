<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Order\Domain\Exception\OrderPaymentAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelOrderPaymentRepository implements OrderPaymentRepositoryInterface
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
            throw OrderPaymentNotFoundException::forId($id->toString());
        }
    }

    public function save(OrderPayment $orderPayment): void
    {
        try {
            $this->repository->save($orderPayment);
        } catch (AggregateAlreadyExists) {
            throw OrderPaymentAlreadyExistsException::forId($orderPayment->id->toString());
        }
    }
}
