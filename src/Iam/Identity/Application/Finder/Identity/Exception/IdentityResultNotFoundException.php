<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class IdentityResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Identity "%s" not found.', $id));
    }
}
