<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Repository;

use Shopping\Checkout\Domain\Cart\Cart;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

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
