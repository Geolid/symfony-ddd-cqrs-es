<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode;

use Shared\Application\VerificationCode\Exception\VerificationCodeAttemptsExceededException;
use Shared\Application\VerificationCode\Exception\VerificationCodeNotFoundException;

final readonly class VerificationCode
{
    private const int MAX_ATTEMPTS = 5;
    private const string EXPIRY = '+15 minutes';

    public function __construct(
        private VerificationCodeStoreInterface $store,
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    /**
     * Issuing a new code for the same (purpose, subjectId) invalidates any prior one.
     */
    public function issue(\BackedEnum $purpose, string $subjectId, \DateTimeImmutable $now): string
    {
        $code = \sprintf('%06d', random_int(0, 999999));

        $this->store->save($purpose, $subjectId, $this->hash($code), $now->modify(self::EXPIRY));

        return $code;
    }

    /**
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     */
    public function verify(\BackedEnum $purpose, string $subjectId, string $code, \DateTimeImmutable $now): bool
    {
        $record = $this->store->find($purpose, $subjectId);

        if (null === $record) {
            throw VerificationCodeNotFoundException::forSubject($purpose, $subjectId);
        }

        if ($record->expiresAt < $now) {
            $this->store->delete($purpose, $subjectId);

            throw VerificationCodeNotFoundException::forSubject($purpose, $subjectId);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            throw VerificationCodeAttemptsExceededException::forSubject($purpose, $subjectId);
        }

        if (!hash_equals($record->codeHash, $this->hash($code))) {
            $this->store->incrementAttempts($purpose, $subjectId);

            return false;
        }

        $this->store->delete($purpose, $subjectId);

        return true;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, $this->secret);
    }
}
