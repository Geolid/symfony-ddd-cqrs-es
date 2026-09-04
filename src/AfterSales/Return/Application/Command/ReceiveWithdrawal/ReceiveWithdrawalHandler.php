<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\ReceiveWithdrawal;

use AfterSales\Return\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ReceiveWithdrawalHandler
{
    public function __construct(
        private WithdrawalRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws WithdrawalNotFoundException
     * @throws WithdrawalAlreadyExistsException
     */
    public function __invoke(ReceiveWithdrawal $command): void
    {
        $withdrawal = $this->repository->load(WithdrawalId::forOrder($command->orderId));
        $withdrawal->receive($this->clock->now());
        $this->repository->save($withdrawal);
    }
}
