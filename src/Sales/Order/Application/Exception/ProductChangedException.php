<?php

declare(strict_types=1);

namespace Sales\Order\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ProductChangedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $productId): self
    {
        return new self(\sprintf('Product with ID "%s" has changed since it was displayed.', $productId));
    }
}
