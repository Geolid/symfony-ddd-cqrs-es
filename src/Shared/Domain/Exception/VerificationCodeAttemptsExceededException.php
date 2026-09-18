<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

final class VerificationCodeAttemptsExceededException extends \DomainException
{
    public static function forSubject(\BackedEnum $purpose, string $subjectId): self
    {
        return new self(\sprintf('Too many failed attempts for the verification code of "%s", purpose "%s".', $subjectId, $purpose->value));
    }
}
