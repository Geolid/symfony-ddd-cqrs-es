<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Command\PlaceOrder;

use Ordering\Order\Domain\Money;
use Ordering\Order\Domain\Order;
use Ordering\Order\Domain\OrderId;
use Ordering\Order\Domain\Repository\OrderRepositoryInterface;
use Psr\Clock\ClockInterface;
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
