<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyInUseException;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class RequestPaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PaymentReferenceAlreadyInUseException
     * @throws PaymentAlreadyClaimedException
     * @throws PaymentAlreadyExistsException
     */
    public function __invoke(RequestPayment $command): void
    {
        $id = PaymentId::fromString($command->id);
        $referenceKey = UniqueKey::for(PaymentUniqueKey::REFERENCE);
        $checkoutSessionKey = UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION);

        try {
            $this->uniqueValues->claim($referenceKey, $command->reference, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw PaymentReferenceAlreadyInUseException::forReference($command->reference, $e);
        }

        try {
            $this->uniqueValues->claim($checkoutSessionKey, $command->checkoutSessionId, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw PaymentAlreadyClaimedException::forCheckoutSession($command->checkoutSessionId, $e);
        }

        $orderPayment = Payment::request(
            id: $id,
            checkoutSessionId: $command->checkoutSessionId,
            amount: Money::fromCents($command->amountInCents),
            reference: PaymentReference::fromString($command->reference),
            checkoutUrl: $command->checkoutUrl,
            requestedAt: $this->clock->now(),
        );

        $this->repository->save($orderPayment);
    }
}
