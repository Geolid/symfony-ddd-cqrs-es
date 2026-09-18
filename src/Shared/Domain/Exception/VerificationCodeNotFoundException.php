<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class VerificationCodeNotFoundException extends \DomainException
{
    public static function forSubject(\BackedEnum $purpose, string $subjectId): self
    {
        return new self(\sprintf('No pending verification code for "%s" of purpose "%s".', $subjectId, $purpose->value));
    }
}
