<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\RejectWithdrawal;

use AfterSales\Return\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RejectWithdrawalHandler
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
    public function __invoke(RejectWithdrawal $command): void
    {
        $withdrawal = $this->repository->load(WithdrawalId::fromString($command->id));
        $withdrawal->reject($command->reason, $this->clock->now());
        $this->repository->save($withdrawal);
    }
}
