<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ConfirmOrderPaymentRefund;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderPaymentAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandUseCase;

#[CommandUseCase]
final readonly class ConfirmOrderPaymentRefundHandler
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
    public function __invoke(ConfirmOrderPaymentRefund $command): void
    {
        $orderPayment = $this->repository->load(OrderPaymentId::fromString($command->id));
        $orderPayment->confirmRefund($this->clock->now());
        $this->repository->save($orderPayment);
    }
}
