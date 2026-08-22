<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

final class CompromisedPasswordException extends \DomainException
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('The password chosen for identity "%s" has appeared in a known data breach.', $identityId));
    }

    public static function forPasswordCredential(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password chosen for password credential "%s" has appeared in a known data breach.', $id->toString()));
    }
}
