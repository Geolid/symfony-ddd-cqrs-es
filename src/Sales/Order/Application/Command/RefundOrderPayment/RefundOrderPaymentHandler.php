<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RefundOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderPaymentAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RefundOrderPaymentHandler
{
    public function __construct(
        private OrderPaymentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderPaymentNotFoundException
     * @throws OrderPaymentAlreadyExistsException
     */
    public function __invoke(RefundOrderPayment $command): void
    {
        $orderPayment = $this->repository->load(OrderPaymentId::fromString($command->id));
        $orderPayment->refund($this->clock->now());
        $this->repository->save($orderPayment);
    }
}
