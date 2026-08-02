<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ProductResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Product with ID "%s" not found.', $id));
    }
}
