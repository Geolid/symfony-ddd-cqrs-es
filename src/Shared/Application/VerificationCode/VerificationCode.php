<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode;

final readonly class VerificationCode
{
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

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, $this->secret);
    }
}
