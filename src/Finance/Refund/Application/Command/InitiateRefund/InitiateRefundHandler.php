<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\InitiateRefund;

use Finance\Refund\Application\Finder\RequestedPayment\Exception\RequestedPaymentResultNotFoundException;
use Finance\Refund\Application\Finder\RequestedPayment\RequestedPaymentFinderInterface;
use Finance\Refund\Domain\Exception\RefundAlreadyExistsException;
use Finance\Refund\Domain\Refund;
use Finance\Refund\Domain\Repository\RefundRepositoryInterface;
use Finance\Refund\Domain\ValueObject\RefundId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Money;

#[CommandHandler]
final readonly class InitiateRefundHandler
{
    public function __construct(
        private RefundRepositoryInterface $repository,
        private RequestedPaymentFinderInterface $requestedPaymentFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws RequestedPaymentResultNotFoundException
     * @throws RefundAlreadyExistsException
     */
    public function __invoke(InitiateRefund $command): void
    {
        $requestedPayment = $this->requestedPaymentFinder->ofOrder($command->orderId);

        $refund = Refund::initiate(
            id: RefundId::generate(),
            paymentId: $requestedPayment->paymentId,
            orderId: $command->orderId,
            amount: Money::fromCents($requestedPayment->amountInCents),
            initiatedAt: $this->clock->now(),
        );

        $this->repository->save($refund);
    }
}
