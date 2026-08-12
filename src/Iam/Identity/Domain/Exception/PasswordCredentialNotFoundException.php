<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\PasswordCredentialId;

final class PasswordCredentialNotFoundException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('Password credential with ID "%s" not found.', $id->toString()));
    }
}
