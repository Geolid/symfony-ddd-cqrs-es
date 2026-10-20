<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\FailPayment;

use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFailableException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class FailPaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PaymentNotFoundException
     * @throws PaymentAlreadyExistsException
     * @throws PaymentNotFailableException
     */
    public function __invoke(FailPayment $command): void
    {
        $payment = $this->repository->load(PaymentId::fromString($command->id));
        $payment->fail($command->orderId, $this->clock->now());
        $this->repository->save($payment);
    }
}
