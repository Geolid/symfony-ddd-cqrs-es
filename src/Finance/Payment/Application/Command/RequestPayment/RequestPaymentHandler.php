<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyTakenException;
use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Finance\Payment\Domain\ValueObject\PaymentUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class RequestPaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PaymentReferenceAlreadyTakenException
     */
    public function __invoke(RequestPayment $command): void
    {
        $id = PaymentId::fromString($command->id);

        // Fast path for a sequential retry; a concurrent one still races past this
        // check (TOCTOU) — save()'s own uniqueness guard below closes that gap.
        if ($this->repository->has($id)) {
            return;
        }

        try {
            $this->uniqueValues->reserve(UniqueKey::for(PaymentUniqueKey::REFERENCE), $command->reference, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw PaymentReferenceAlreadyTakenException::forReference($command->reference, $e);
        }

        $orderPayment = Payment::request(
            id: $id,
            orderId: $command->orderId,
            amount: Money::fromCents($command->amountInCents),
            reference: PaymentReference::fromString($command->reference),
            checkoutUrl: $command->checkoutUrl,
            requestedAt: $this->clock->now(),
        );

        try {
            $this->repository->save($orderPayment);
        } catch (PaymentAlreadyExistsException) {
            return;
        }
    }
}
