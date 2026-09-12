<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\AddCartProduct\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ProductNotListedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $productId): self
    {
        return new self(\sprintf('Product "%s" is not listed.', $productId));
    }
}
