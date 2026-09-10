<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CartLine;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<CartLineResult>
 */
interface CartLineFinderInterface extends IterableFinderInterface
{
    public function byCart(string $cartId): static;
}
