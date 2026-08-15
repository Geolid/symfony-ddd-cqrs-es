<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\PasswordCredentialId;

final class CompromisedPasswordException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password chosen for credential "%s" has appeared in a known data breach.', $id->toString()));
    }
}
