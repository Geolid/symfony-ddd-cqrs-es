<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ProductLabelAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLabel(string $label, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('A product with label "%s" is already listed.', $label),
            previous: $previous,
        );
    }
}
