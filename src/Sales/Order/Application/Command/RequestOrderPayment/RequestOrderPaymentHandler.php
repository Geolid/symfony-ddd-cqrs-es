<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Order\Domain\ValueObject\PaymentReference;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\ValueObject\Money;

#[AsCommandHandler]
final readonly class RequestOrderPaymentHandler
{
    public function __construct(
        private OrderPaymentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RequestOrderPayment $command): void
    {
        $id = OrderPaymentId::fromString($command->id);

        if ($this->repository->has($id)) {
            return;
        }

        $orderPayment = OrderPayment::request(
            id: $id,
            orderId: $command->orderId,
            customerId: $command->customerId,
            buyerAddress: $command->buyerAddress,
            amount: Money::fromCents($command->amountInCents),
            reference: PaymentReference::fromString($command->reference),
            checkoutUrl: $command->checkoutUrl,
            requestedAt: $this->clock->now(),
        );

        $this->repository->save($orderPayment);
    }
}
