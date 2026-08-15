<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\PasswordCredentialId;

final class PasswordUnchangedException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password credential "%s" cannot be changed to the same password.', $id->toString()));
    }
}
