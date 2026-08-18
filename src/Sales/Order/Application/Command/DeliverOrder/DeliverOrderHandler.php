<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\DeliverOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class DeliverOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    public function __invoke(DeliverOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->deliver($this->clock->now());
        $this->repository->save($order);
    }
}
