<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\FailOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderPaymentAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class FailOrderPaymentHandler
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
    public function __invoke(FailOrderPayment $command): void
    {
        $orderPayment = $this->repository->load(OrderPaymentId::fromString($command->id));
        $orderPayment->fail($this->clock->now());
        $this->repository->save($orderPayment);
    }
}
