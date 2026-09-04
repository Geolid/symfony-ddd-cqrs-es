<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Command\ApproveWithdrawal;

use AfterSales\Withdrawal\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Withdrawal\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ApproveWithdrawalHandler
{
    public function __construct(
        private WithdrawalRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws WithdrawalNotFoundException
     * @throws WithdrawalNotReceivedException
     * @throws WithdrawalAlreadyExistsException
     */
    public function __invoke(ApproveWithdrawal $command): void
    {
        $withdrawal = $this->repository->load(WithdrawalId::forOrder($command->orderId));
        $withdrawal->approve($this->clock->now());
        $this->repository->save($withdrawal);
    }
}
