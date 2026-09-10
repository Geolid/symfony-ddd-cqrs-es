<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Repository;

use Shopping\Checkout\Domain\Exception\ShopperAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Shopper;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

interface ShopperRepositoryInterface
{
    public function has(ShopperId $id): bool;

    /**
     * @throws ShopperNotFoundException
     */
    public function load(ShopperId $id): Shopper;

    /**
     * @throws ShopperAlreadyExistsException
     */
    public function save(Shopper $shopper): void;
}
