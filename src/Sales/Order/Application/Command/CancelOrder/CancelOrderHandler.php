<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
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
     * @throws OrderBelongsToAnotherCustomerException
     */
    public function __invoke(CancelOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->cancel($command->customerId, $this->clock->now());
        $this->repository->save($order);
    }
}
