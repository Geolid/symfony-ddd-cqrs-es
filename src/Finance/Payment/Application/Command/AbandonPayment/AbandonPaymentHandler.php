<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\AbandonPayment;

use Finance\Payment\Application\Uniqueness\PaymentUniqueKey;
use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class AbandonPaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PaymentNotFoundException
     * @throws PaymentAlreadyExistsException
     */
    public function __invoke(AbandonPayment $command): void
    {
        $id = PaymentId::fromString($command->id);
        $orderPayment = $this->repository->load($id);
        $orderPayment->abandon($this->clock->now());
        $this->repository->save($orderPayment);

        $this->uniqueValues->release(UniqueKey::for(PaymentUniqueKey::CART), $command->id);
    }
}
