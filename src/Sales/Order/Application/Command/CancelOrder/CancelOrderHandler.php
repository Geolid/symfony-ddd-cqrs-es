<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\OrderId;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private OrderPaymentFinderInterface $orderPaymentFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyRequestedException
     */
    public function __invoke(CancelOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));

        if (null !== $this->orderPaymentFinder->ofOrder($command->id)) {
            throw OrderPaymentAlreadyRequestedException::forOrderId($command->id);
        }

        $order->cancel($this->clock->now());
        $this->repository->save($order);
    }
}
