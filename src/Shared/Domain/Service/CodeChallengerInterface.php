<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\ValueObject\VerificationCodeKey;

interface CodeChallengerInterface
{
    /**
     * Issuing a new code for the same key invalidates any prior one.
     */
    public function issue(VerificationCodeKey $key, \DateTimeImmutable $now): string;

    /**
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     */
    public function verify(VerificationCodeKey $key, #[\SensitiveParameter] string $code, \DateTimeImmutable $now): bool;
}
