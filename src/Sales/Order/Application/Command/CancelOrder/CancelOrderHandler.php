<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Enum\AppOrderPaymentStatus;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
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
     * @throws OrderPaymentAlreadyCapturedException
     */
    public function __invoke(CancelOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));

        $orderPayment = $this->orderPaymentFinder->ofOrder($command->id);

        if (null !== $orderPayment && AppOrderPaymentStatus::from($orderPayment->status)->isCaptured()) {
            throw OrderPaymentAlreadyCapturedException::forOrderId($command->id);
        }

        $order->cancel($this->clock->now());
        $this->repository->save($order);
    }
}
