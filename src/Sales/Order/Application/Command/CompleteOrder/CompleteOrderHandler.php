<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CompleteOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderNotCompletableException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\Service\ReturnWindow;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class CompleteOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
        private ReturnWindow $returnWindow,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderNotCompletableException
     */
    public function __invoke(CompleteOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->complete($this->clock->now(), $this->returnWindow);
        $this->repository->save($order);
    }
}
