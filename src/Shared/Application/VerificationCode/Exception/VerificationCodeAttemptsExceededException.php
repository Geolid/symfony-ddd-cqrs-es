<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class VerificationCodeAttemptsExceededException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forSubject(\BackedEnum $purpose, string $subjectId): self
    {
        return new self(\sprintf('Too many failed attempts for the verification code of "%s", purpose "%s".', $subjectId, $purpose->value));
    }
}
