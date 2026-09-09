<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Repository;

use Sales\Ordering\Domain\Cart\Cart;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;

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
