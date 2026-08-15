<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;

final class CompromisedPasswordException extends \DomainException
{
    public static function forIdentity(IdentityId $id): self
    {
        return new self(\sprintf('The password chosen for identity "%s" has appeared in a known data breach.', $id->toString()));
    }

    public static function forCredential(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password chosen for credential "%s" has appeared in a known data breach.', $id->toString()));
    }
}
