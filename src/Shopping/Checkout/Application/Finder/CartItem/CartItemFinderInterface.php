<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CartItem;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<CartItemResult>
 */
interface CartItemFinderInterface extends IterableFinderInterface
{
    public function byCart(string $cartId): static;
}
