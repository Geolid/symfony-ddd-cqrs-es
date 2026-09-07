<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Finder\Subject\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class SubjectResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Subject "%s" not found.', $id));
    }
}
