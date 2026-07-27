<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Command\CancelOrder;

use Ordering\Order\Domain\Exception\OrderAlreadyCancelledException;
use Ordering\Order\Domain\Exception\OrderNotFoundException;
use Ordering\Order\Domain\OrderId;
use Ordering\Order\Domain\Repository\OrderRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderAlreadyCancelledException
     */
    public function __invoke(CancelOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->cancel($this->clock->now());
        $this->repository->save($order);
    }
}
