<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\FailRefund;

use Finance\Refund\Application\Finder\RequestedPayment\Exception\RequestedPaymentResultNotFoundException;
use Finance\Refund\Application\Finder\RequestedPayment\RequestedPaymentFinderInterface;
use Finance\Refund\Domain\Exception\RefundAlreadyExistsException;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\Repository\RefundRepositoryInterface;
use Finance\Refund\Domain\ValueObject\RefundId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class FailRefundHandler
{
    public function __construct(
        private RefundRepositoryInterface $repository,
        private RequestedPaymentFinderInterface $requestedPaymentFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws RequestedPaymentResultNotFoundException
     * @throws RefundNotFoundException
     * @throws RefundAlreadyExistsException
     */
    public function __invoke(FailRefund $command): void
    {
        $requestedPayment = $this->requestedPaymentFinder->ofOrder($command->orderId);
        $id = RefundId::forPayment($requestedPayment->paymentId);

        $refund = $this->repository->load($id);
        $refund->fail($this->clock->now());
        $this->repository->save($refund);
    }
}
