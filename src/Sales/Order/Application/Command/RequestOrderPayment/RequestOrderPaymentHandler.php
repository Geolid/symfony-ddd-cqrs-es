<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Domain\Exception\OrderPaymentAlreadyExistsException;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentUniqueKey;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Application\Command\CommandUseCase;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\UniqueKey;

#[CommandUseCase]
final readonly class RequestOrderPaymentHandler
{
    public function __construct(
        private OrderPaymentRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PaymentReferenceAlreadyTakenException
     */
    public function __invoke(RequestOrderPayment $command): void
    {
        $id = OrderPaymentId::fromString($command->id);

        // Fast path for a sequential retry; a concurrent one still races past this
        // check (TOCTOU) — save()'s own uniqueness guard below closes that gap.
        if ($this->repository->has($id)) {
            return;
        }

        try {
            $this->uniqueValues->reserve(UniqueKey::for(OrderPaymentUniqueKey::REFERENCE), $command->reference, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw PaymentReferenceAlreadyTakenException::forReference($command->reference, $e);
        }

        $orderPayment = OrderPayment::request(
            id: $id,
            orderId: $command->orderId,
            amount: Money::fromCents($command->amountInCents),
            reference: PaymentReference::fromString($command->reference),
            checkoutUrl: $command->checkoutUrl,
            requestedAt: $this->clock->now(),
        );

        try {
            $this->repository->save($orderPayment);
        } catch (OrderPaymentAlreadyExistsException) {
            return;
        }
    }
}
