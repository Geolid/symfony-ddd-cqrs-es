<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CompleteOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderNotCompletableException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CompleteOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderNotCompletableException
     * @throws OrderAlreadyExistsException
     */
    public function __invoke(CompleteOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->complete($this->clock->now());
        $this->repository->save($order);
    }
}
