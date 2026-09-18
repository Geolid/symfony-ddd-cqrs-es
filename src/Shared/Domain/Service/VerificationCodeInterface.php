<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;

interface VerificationCodeInterface
{
    /**
     * Issuing a new code for the same (purpose, subjectId) invalidates any prior one.
     */
    public function issue(\BackedEnum $purpose, string $subjectId, \DateTimeImmutable $now): string;

    /**
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     */
    public function verify(\BackedEnum $purpose, string $subjectId, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool;
}
