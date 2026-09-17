<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class VerificationCodeNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forSubject(\BackedEnum $purpose, string $subjectId): self
    {
        return new self(\sprintf('No pending verification code for "%s" of purpose "%s".', $subjectId, $purpose->value));
    }
}
