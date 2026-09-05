<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\Repository;

use Finance\Refund\Domain\Exception\RefundAlreadyExistsException;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\Refund;
use Finance\Refund\Domain\ValueObject\RefundId;

interface RefundRepositoryInterface
{
    public function has(RefundId $id): bool;

    /**
     * @throws RefundNotFoundException
     */
    public function load(RefundId $id): Refund;

    /**
     * @throws RefundAlreadyExistsException
     */
    public function save(Refund $refund): void;
}
