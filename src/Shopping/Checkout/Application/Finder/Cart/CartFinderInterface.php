<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Cart;

use Shared\Application\Finder\IterableFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;

/**
 * @extends IterableFinderInterface<CartResult>
 */
interface CartFinderInterface extends IterableFinderInterface
{
    /**
     * @throws CartResultNotFoundException
     */
    public function ofId(string $id): CartResult;

    public function byProductId(string $productId): static;
}
