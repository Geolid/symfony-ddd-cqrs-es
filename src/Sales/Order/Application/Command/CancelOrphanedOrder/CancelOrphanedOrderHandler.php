<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrphanedOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelOrphanedOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderBelongsToAnotherBuyerException
     * @throws OrderAlreadyExistsException
     */
    public function __invoke(CancelOrphanedOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));

        try {
            $order->cancel($command->buyerId, $this->clock->now());
            $this->repository->save($order);
        } catch (OrderNotCancellableException) {
        }
    }
}
