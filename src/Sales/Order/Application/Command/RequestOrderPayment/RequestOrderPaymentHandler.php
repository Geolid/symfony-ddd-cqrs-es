<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\OrderPaymentUniqueValue;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Money;

#[AsCommandHandler]
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

        if ($this->repository->has($id)) {
            return;
        }

        try {
            $this->uniqueValues->reserve(OrderPaymentUniqueValue::REFERENCE, $command->reference);
        } catch (UniqueValueAlreadyTakenException) {
            throw PaymentReferenceAlreadyTakenException::forReference($command->reference);
        }

        $orderPayment = OrderPayment::request(
            id: $id,
            orderId: $command->orderId,
            amount: Money::fromCents($command->amountInCents),
            reference: PaymentReference::fromString($command->reference),
            checkoutUrl: $command->checkoutUrl,
            requestedAt: $this->clock->now(),
        );

        $this->repository->save($orderPayment);
    }
}
