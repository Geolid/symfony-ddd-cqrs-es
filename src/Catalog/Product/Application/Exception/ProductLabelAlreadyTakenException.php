<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ProductLabelAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLabel(string $label): self
    {
        return new self(\sprintf('A product with label "%s" is already listed.', $label));
    }
}
