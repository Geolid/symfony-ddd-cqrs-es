<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

abstract class VerificationCodeRequestedTooRecentlyException extends \DomainException
{
    protected function __construct(
        string $message,
        public readonly \DateTimeImmutable $retryAt,
    ) {
        parent::__construct($message);
    }
}
