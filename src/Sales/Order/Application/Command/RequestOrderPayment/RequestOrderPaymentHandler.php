<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderPayment;

use Psr\Clock\ClockInterface;
use Sales\Order\Domain\OrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
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
            $id,
            $command->orderId,
            $command->customerId,
            $command->buyerAddress,
            Money::fromCents($command->amountInCents),
            $command->reference,
            $this->clock->now(),
        );

        $this->repository->save($orderPayment);
    }
}
