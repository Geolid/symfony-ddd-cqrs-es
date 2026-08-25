<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderPaymentAlreadyExistsException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelOrderPaymentHandler
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
    public function __invoke(CancelOrderPayment $command): void
    {
        $id = OrderPaymentId::fromString($command->id);

        if (!$this->repository->has($id)) {
            return;
        }

        $orderPayment = $this->repository->load($id);
        $orderPayment->cancel($this->clock->now());
        $this->repository->save($orderPayment);
    }
}
