<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CartLine;

/**
 * @extends \IteratorAggregate<int, CartLineResult>
 */
interface CartLineFinderInterface extends \IteratorAggregate
{
    public function byCart(string $cartId): static;
}
