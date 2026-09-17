<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

final class PasswordResetRequestedTooRecentlyException extends \DomainException
{
    public static function forId(PasswordCredentialId $id): self
    {
        return new self(\sprintf('A password reset was already requested too recently for credential "%s".', $id->toString()));
    }
}
