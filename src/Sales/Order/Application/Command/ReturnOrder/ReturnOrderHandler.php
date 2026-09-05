<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ReturnOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ReturnOrderHandler
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
    public function __invoke(ReturnOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->returnGoods($this->clock->now());
        $this->repository->save($order);
    }
}
