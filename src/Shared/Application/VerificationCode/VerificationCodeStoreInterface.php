<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode;

interface VerificationCodeStoreInterface
{
    /**
     * Replaces any prior record for the same (purpose, subjectId).
     */
    public function save(\BackedEnum $purpose, string $subjectId, string $codeHash, \DateTimeImmutable $expiresAt): void;

    public function find(\BackedEnum $purpose, string $subjectId): ?VerificationCodeRecord;

    public function incrementAttempts(\BackedEnum $purpose, string $subjectId): void;

    public function delete(\BackedEnum $purpose, string $subjectId): void;
}
