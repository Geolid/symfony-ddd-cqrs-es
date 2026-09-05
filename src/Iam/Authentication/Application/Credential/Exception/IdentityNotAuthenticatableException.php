<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class IdentityNotAuthenticatableException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Identity "%s" is not authenticatable.', $identityId));
    }
}
