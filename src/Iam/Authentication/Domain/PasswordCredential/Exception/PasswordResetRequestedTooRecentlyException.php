<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Shared\Domain\Exception\VerificationCodeRequestedTooRecentlyException;

final class PasswordResetRequestedTooRecentlyException extends VerificationCodeRequestedTooRecentlyException
{
    public static function forId(PasswordCredentialId $id, \DateTimeImmutable $retryAt): self
    {
        return new self(\sprintf('A password reset was already requested too recently for credential "%s".', $id->toString()), $retryAt);
    }
}
