<?php

declare(strict_types=1);

namespace Shared\Application\VerificationCode;

final readonly class VerificationCodeRecord
{
    public function __construct(
        public string $codeHash,
        public \DateTimeImmutable $expiresAt,
        public int $attempts,
    ) {
    }
}
