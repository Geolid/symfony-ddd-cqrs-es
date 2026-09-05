<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\AbortOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class AbortOrderHandler
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
    public function __invoke(AbortOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->abort($this->clock->now());
        $this->repository->save($order);
    }
}
