<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

final class SamePasswordException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password for password credential "%s" cannot be changed to the same password.', $id->toString()));
    }
}
