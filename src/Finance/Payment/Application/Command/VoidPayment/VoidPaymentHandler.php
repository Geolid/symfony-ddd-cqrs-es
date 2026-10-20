<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\VoidPayment;

use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class VoidPaymentHandler
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
    public function __invoke(VoidPayment $command): void
    {
        $id = PaymentId::fromString($command->id);

        if (!$this->repository->has($id)) {
            return;
        }

        $payment = $this->repository->load($id);
        $payment->void($this->clock->now());
        $this->repository->save($payment);
    }
}
