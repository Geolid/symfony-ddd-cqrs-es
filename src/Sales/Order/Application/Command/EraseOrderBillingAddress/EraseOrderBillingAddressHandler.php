<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\EraseOrderBillingAddress;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\AggregateNotFoundException;

#[AsCommandHandler]
final readonly class EraseOrderBillingAddressHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AggregateNotFoundException
     */
    public function __invoke(EraseOrderBillingAddress $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));

        $order->eraseBillingAddress($this->clock->now());
        $this->repository->save($order);
    }
}
