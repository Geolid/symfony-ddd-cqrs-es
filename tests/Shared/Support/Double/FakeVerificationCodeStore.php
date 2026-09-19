<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Shared\Application\VerificationCode\VerificationCodeRecord;
use Shared\Application\VerificationCode\VerificationCodeStoreInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;

final class FakeVerificationCodeStore implements VerificationCodeStoreInterface
{
    /** @var array<string, VerificationCodeRecord> */
    private array $records = [];

    public function save(VerificationCodeKey $key, string $codeHash, \DateTimeImmutable $expiresAt): void
    {
        $this->records[$key->toString()] = new VerificationCodeRecord($codeHash, $expiresAt, 0);
    }

    public function find(VerificationCodeKey $key): ?VerificationCodeRecord
    {
        return $this->records[$key->toString()] ?? null;
    }

    public function incrementAttempts(VerificationCodeKey $key): void
    {
        $record = $this->records[$key->toString()] ?? null;

        if (null === $record) {
            return;
        }

        $this->records[$key->toString()] = new VerificationCodeRecord($record->codeHash, $record->expiresAt, $record->attempts + 1);
    }

    public function delete(VerificationCodeKey $key): void
    {
        unset($this->records[$key->toString()]);
    }
}
