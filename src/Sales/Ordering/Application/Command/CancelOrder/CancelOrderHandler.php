<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\CancelOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderBelongsToAnotherCustomerException
     * @throws OrderNotCancellableException
     * @throws OrderAlreadyExistsException
     */
    public function __invoke(CancelOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->cancel($command->customerId, $this->clock->now());
        $this->repository->save($order);
    }
}
