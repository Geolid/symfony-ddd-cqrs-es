<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Iam\Identity\Domain\ValueObject\IdentityId;

final class ConfirmationRequestedTooRecentlyException extends \DomainException
{
    private function __construct(
        string $message,
        public readonly \DateTimeImmutable $retryAt,
    ) {
        parent::__construct($message);
    }

    public static function forId(IdentityId $id, \DateTimeImmutable $retryAt): self
    {
        return new self(\sprintf('A confirmation was already requested too recently for identity "%s".', $id->toString()), $retryAt);
    }
}
