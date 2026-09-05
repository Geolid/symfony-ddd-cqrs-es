<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\InitiateRefund;

use Finance\Refund\Application\Exception\PlacedPaymentResultNotFoundException;
use Finance\Refund\Application\Finder\PlacedPayment\PlacedPaymentFinderInterface;
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
        private PlacedPaymentFinderInterface $placedPaymentFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PlacedPaymentResultNotFoundException
     * @throws RefundAlreadyExistsException
     */
    public function __invoke(InitiateRefund $command): void
    {
        $placedPayment = $this->placedPaymentFinder->ofOrder($command->orderId);
        $id = RefundId::forPayment($placedPayment->paymentId);

        if ($this->repository->has($id)) {
            return;
        }

        $refund = Refund::initiate(
            id: $id,
            paymentId: $placedPayment->paymentId,
            orderId: $command->orderId,
            amount: Money::fromCents($placedPayment->amountInCents),
            initiatedAt: $this->clock->now(),
        );

        try {
            $this->repository->save($refund);
        } catch (RefundAlreadyExistsException) {
            return;
        }
    }
}
