<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Finder\Erasure\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class ErasureResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Erasure "%s" not found.', $id));
    }
}
