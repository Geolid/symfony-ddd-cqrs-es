<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CaptureOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\Exception\OrderPaymentInvalidTransitionException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class CaptureOrderPaymentHandler
{
    public function __construct(
        private OrderPaymentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderPaymentNotFoundException
     * @throws OrderPaymentInvalidTransitionException
     */
    public function __invoke(CaptureOrderPayment $command): void
    {
        $orderPayment = $this->repository->load(OrderPaymentId::fromString($command->id));
        $orderPayment->capture($this->clock->now());

        $this->repository->save($orderPayment);
    }
}
