<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Application\VerificationCode\VerificationCodeRecord;
use Shared\Application\VerificationCode\VerificationCodeStoreInterface;

final class FakeVerificationCodeStore implements VerificationCodeStoreInterface
{
    /** @var array<string, VerificationCodeRecord> */
    private array $records = [];

    public function save(\BackedEnum $purpose, string $subjectId, string $codeHash, \DateTimeImmutable $expiresAt): void
    {
        $this->records[$this->key($purpose, $subjectId)] = new VerificationCodeRecord($codeHash, $expiresAt, 0);
    }

    public function find(\BackedEnum $purpose, string $subjectId): ?VerificationCodeRecord
    {
        return $this->records[$this->key($purpose, $subjectId)] ?? null;
    }

    public function incrementAttempts(\BackedEnum $purpose, string $subjectId): void
    {
        $key = $this->key($purpose, $subjectId);
        $record = $this->records[$key] ?? null;

        if (null === $record) {
            return;
        }

        $this->records[$key] = new VerificationCodeRecord($record->codeHash, $record->expiresAt, $record->attempts + 1);
    }

    public function delete(\BackedEnum $purpose, string $subjectId): void
    {
        unset($this->records[$this->key($purpose, $subjectId)]);
    }

    private function key(\BackedEnum $purpose, string $subjectId): string
    {
        return \sprintf('%s:%s', $purpose->value, $subjectId);
    }
}
