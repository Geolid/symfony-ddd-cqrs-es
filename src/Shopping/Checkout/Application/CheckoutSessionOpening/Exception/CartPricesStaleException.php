<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\CheckoutSessionOpening\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class CartPricesStaleException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forProduct(string $productId): self
    {
        return new self(\sprintf('Product "%s" price has changed since it was added to the cart.', $productId));
    }
}
