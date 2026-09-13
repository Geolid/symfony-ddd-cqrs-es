<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Cart;

use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;

interface CartFinderInterface
{
    /**
     * @throws CartResultNotFoundException
     */
    public function ofId(string $id): CartResult;
}
