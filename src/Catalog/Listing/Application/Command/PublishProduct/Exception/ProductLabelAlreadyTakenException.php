<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\PublishProduct\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ProductLabelAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLabel(string $label, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('Label "%s" is already in use.', $label),
            previous: $previous,
        );
    }
}
