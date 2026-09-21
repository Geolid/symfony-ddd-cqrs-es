<?php

declare(strict_types=1);

namespace Storefront\Security\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class AccountSuspendedException extends CustomUserMessageAccountStatusException
{
    public static function forIdentity(string $identityId): self
    {
        $exception = new self(\sprintf('Identity "%s" is suspended.', $identityId));
        $exception->setSafeMessage('Your account is suspended.');

        return $exception;
    }
}
