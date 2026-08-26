<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ConfirmOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
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
     */
    public function __invoke(ConfirmOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->confirm($this->clock->now());
        $this->repository->save($order);
    }
}
