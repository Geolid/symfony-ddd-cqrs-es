<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\PasswordCredentialId;

final class PasswordCredentialNotFoundException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('No password credential carries the id "%s".', $id->toString()));
    }
}
