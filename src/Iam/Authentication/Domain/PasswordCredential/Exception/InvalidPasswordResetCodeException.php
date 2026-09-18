<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

final class InvalidPasswordResetCodeException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('The password reset code for password credential "%s" is invalid.', $id->toString()));
    }
}
