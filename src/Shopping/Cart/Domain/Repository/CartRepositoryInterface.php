<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Repository;

use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Exception\CartAlreadyExistsException;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Cart\Domain\ValueObject\CartId;

interface CartRepositoryInterface
{
    public function has(CartId $id): bool;

    /**
     * @throws CartNotFoundException
     */
    public function load(CartId $id): Cart;

    /**
     * @throws CartAlreadyExistsException
     */
    public function save(Cart $cart): void;
}
