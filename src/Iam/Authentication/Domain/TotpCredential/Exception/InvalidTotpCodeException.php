<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Exception;

final class InvalidTotpCodeException extends \DomainException
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('The code provided for identity "%s" is invalid.', $identityId));
    }
}
