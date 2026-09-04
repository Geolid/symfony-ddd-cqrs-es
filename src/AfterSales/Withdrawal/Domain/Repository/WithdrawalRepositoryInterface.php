<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\Repository;

use AfterSales\Withdrawal\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use AfterSales\Withdrawal\Domain\Withdrawal;

interface WithdrawalRepositoryInterface
{
    public function has(WithdrawalId $id): bool;

    /**
     * @throws WithdrawalNotFoundException
     */
    public function load(WithdrawalId $id): Withdrawal;

    /**
     * @throws WithdrawalAlreadyExistsException
     */
    public function save(Withdrawal $withdrawal): void;
}
