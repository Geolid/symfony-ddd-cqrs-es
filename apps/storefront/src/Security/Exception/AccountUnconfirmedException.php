<?php

declare(strict_types=1);

namespace Storefront\Security\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class AccountUnconfirmedException extends CustomUserMessageAccountStatusException
{
    public static function forIdentity(string $identityId): self
    {
        $exception = new self(\sprintf('Identity "%s" is not confirmed.', $identityId));
        $exception->setSafeMessage('Your account is not confirmed.');

        return $exception;
    }
}
