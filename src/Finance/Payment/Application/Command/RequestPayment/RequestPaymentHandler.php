<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Command\RequestPayment;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyRequestedException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyTakenException;
use Finance\Payment\Application\Uniqueness\PaymentUniqueKey;
use Finance\Payment\Domain\Exception\PaymentAlreadyExistsException;
use Finance\Payment\Domain\Payment;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Payment\Domain\ValueObject\PaymentReference;
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
     * @throws PaymentAlreadyRequestedException
     */
    public function __invoke(RequestPayment $command): void
    {
        $id = PaymentId::fromString($command->id);
        $referenceKey = UniqueKey::for(PaymentUniqueKey::REFERENCE);
        $cartKey = UniqueKey::for(PaymentUniqueKey::CART);

        try {
            $this->uniqueValues->reserve($referenceKey, $command->reference, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw PaymentReferenceAlreadyTakenException::forReference($command->reference, $e);
        }

        try {
            $this->uniqueValues->reserve($cartKey, $command->cartId, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw PaymentAlreadyRequestedException::forCart($command->cartId, $e);
        }

        $orderPayment = Payment::request(
            id: $id,
            cartId: $command->cartId,
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
