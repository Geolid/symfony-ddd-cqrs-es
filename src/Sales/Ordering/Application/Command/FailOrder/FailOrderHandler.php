<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\FailOrder;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class FailOrderHandler
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
    public function __invoke(FailOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->fail($this->clock->now());
        $this->repository->save($order);
    }
}
