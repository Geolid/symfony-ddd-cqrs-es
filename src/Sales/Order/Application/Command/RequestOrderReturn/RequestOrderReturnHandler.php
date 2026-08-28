<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderReturn;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Exception\OrderNotReturnableException;
use Sales\Order\Domain\Exception\OrderReturnWindowExpiredException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestOrderReturnHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderBelongsToAnotherCustomerException
     * @throws OrderNotReturnableException
     * @throws OrderReturnWindowExpiredException
     * @throws OrderAlreadyExistsException
     */
    public function __invoke(RequestOrderReturn $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));
        $order->requestReturn($command->customerId, $this->clock->now());
        $this->repository->save($order);
    }
}
