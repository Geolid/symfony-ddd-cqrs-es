<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\Repository;

use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\ValueObject\BuyerId;

interface BuyerRepositoryInterface
{
    public function has(BuyerId $id): bool;

    /**
     * @throws BuyerNotFoundException
     */
    public function load(BuyerId $id): Buyer;

    /**
     * @throws BuyerAlreadyExistsException
     */
    public function save(Buyer $buyer): void;
}
