<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;

interface VerificationCodeVerifierInterface
{
    /**
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     */
    public function verify(\BackedEnum $purpose, string $subjectId, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool;
}
