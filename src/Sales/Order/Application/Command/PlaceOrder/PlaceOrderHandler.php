<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\PlaceOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Money;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\OrderId;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class PlaceOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(PlaceOrder $command): void
    {
        $order = Order::place(
            OrderId::fromString($command->id),
            $command->customerId,
            Money::fromCents($command->totalAmountInCents),
            $this->clock->now(),
        );

        $this->repository->save($order);
    }
}
