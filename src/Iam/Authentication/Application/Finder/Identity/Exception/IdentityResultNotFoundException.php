<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\Identity\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class IdentityResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $identityId): self
    {
        return new self(\sprintf('Identity "%s" not found.', $identityId));
    }
}
