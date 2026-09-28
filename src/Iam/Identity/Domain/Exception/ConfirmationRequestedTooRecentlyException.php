<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Shared\Domain\Exception\VerificationCodeRequestedTooRecentlyException;

final class ConfirmationRequestedTooRecentlyException extends VerificationCodeRequestedTooRecentlyException
{
    public static function forId(IdentityId $id, \DateTimeImmutable $retryAt): self
    {
        return new self(\sprintf('A confirmation was already requested too recently for identity "%s".', $id->toString()), $retryAt);
    }
}
