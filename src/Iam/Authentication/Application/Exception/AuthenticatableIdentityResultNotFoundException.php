<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class AuthenticatableIdentityResultNotFoundException extends ResultNotFoundException
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Authenticatable identity "%s" not found.', $identityId));
    }
}
