<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Command\ConfirmRefund;

use Finance\Refund\Domain\Exception\RefundAlreadyExistsException;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\Repository\RefundRepositoryInterface;
use Finance\Refund\Domain\ValueObject\RefundId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ConfirmRefundHandler
{
    public function __construct(
        private RefundRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws RefundNotFoundException
     * @throws RefundAlreadyExistsException
     */
    public function __invoke(ConfirmRefund $command): void
    {
        $refund = $this->repository->load(RefundId::fromString($command->id));
        $refund->confirm($this->clock->now());
        $this->repository->save($refund);
    }
}
