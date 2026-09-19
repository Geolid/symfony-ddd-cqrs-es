<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode;

use Shared\Domain\ValueObject\VerificationCodeKey;

interface VerificationCodeStoreInterface
{
    /**
     * Replaces any prior record for the same key.
     */
    public function save(VerificationCodeKey $key, string $codeHash, \DateTimeImmutable $expiresAt): void;

    public function find(VerificationCodeKey $key): ?VerificationCodeRecord;

    public function incrementAttempts(VerificationCodeKey $key): void;

    public function delete(VerificationCodeKey $key): void;
}
