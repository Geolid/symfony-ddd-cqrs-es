<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;

final class PasswordResetRequestedTooRecentlyException extends \DomainException
{
    private function __construct(
        string $message,
        public readonly \DateTimeImmutable $retryAt,
    ) {
        parent::__construct($message);
    }

    public static function forId(PasswordCredentialId $id, \DateTimeImmutable $retryAt): self
    {
        return new self(\sprintf('A password reset was already requested too recently for credential "%s".', $id->toString()), $retryAt);
    }
}
