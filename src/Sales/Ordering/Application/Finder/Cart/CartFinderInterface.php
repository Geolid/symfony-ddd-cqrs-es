<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Cart;

use Sales\Ordering\Application\Finder\Cart\Exception\CartResultNotFoundException;

interface CartFinderInterface
{
    /**
     * @throws CartResultNotFoundException
     */
    public function ofId(string $cartId): CartResult;
}
