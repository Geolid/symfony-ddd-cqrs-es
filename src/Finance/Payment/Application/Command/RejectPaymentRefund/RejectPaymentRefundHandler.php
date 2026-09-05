<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RejectPaymentRefund;

use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RejectPaymentRefundHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PaymentNotFoundException
     * @throws PaymentAlreadyExistsException
     */
    public function __invoke(RejectPaymentRefund $command): void
    {
        $payment = $this->repository->load(PaymentId::fromString($command->id));
        $payment->rejectRefund($this->clock->now());
        $this->repository->save($payment);
    }
}
