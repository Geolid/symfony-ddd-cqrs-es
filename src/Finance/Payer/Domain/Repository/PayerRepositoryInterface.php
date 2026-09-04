<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\Repository;

use Finance\Payer\Domain\Exception\PayerAlreadyExistsException;
use Finance\Payer\Domain\Exception\PayerNotFoundException;
use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\ValueObject\PayerId;

interface PayerRepositoryInterface
{
    public function has(PayerId $id): bool;

    /**
     * @throws PayerNotFoundException
     */
    public function load(PayerId $id): Payer;

    /**
     * @throws PayerAlreadyExistsException
     */
    public function save(Payer $payer): void;
}
