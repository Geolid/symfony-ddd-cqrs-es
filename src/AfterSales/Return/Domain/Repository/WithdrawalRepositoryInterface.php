<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Repository;

use AfterSales\Return\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\Withdrawal;

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
