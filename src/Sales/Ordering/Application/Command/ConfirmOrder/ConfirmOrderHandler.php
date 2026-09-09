<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\ConfirmOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Domain\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ConfirmOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderAlreadyExistsException
     */
    public function __invoke(ConfirmOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->confirm($this->clock->now());
        $this->repository->save($order);
    }
}
