<?php

declare(strict_types=1);

namespace Shared\Application\Exception;

final class ActingIdentityNotActiveException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Identity "%s" is not active and cannot act.', $identityId));
    }
}
