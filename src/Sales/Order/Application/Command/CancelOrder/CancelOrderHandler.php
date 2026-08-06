<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrder;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private OrderPaymentRepositoryInterface $orderPaymentRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyCapturedException
     * @throws OrderPaymentNotFoundException
     */
    public function __invoke(CancelOrder $command): void
    {
        $order = $this->repository->load(OrderId::fromString($command->id));

        $orderPaymentId = OrderPaymentId::forOrder($command->id);

        if ($this->orderPaymentRepository->has($orderPaymentId)
            && $this->orderPaymentRepository->load($orderPaymentId)->status()->isCaptured()
        ) {
            throw OrderPaymentAlreadyCapturedException::forOrderId($command->id);
        }

        $order->cancel($this->clock->now());
        $this->repository->save($order);
    }
}
