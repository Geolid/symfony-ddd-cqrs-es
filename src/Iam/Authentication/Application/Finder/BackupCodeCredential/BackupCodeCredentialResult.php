<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\BackupCodeCredential;

final readonly class BackupCodeCredentialResult
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $generatedAt,
        public ?\DateTimeImmutable $regeneratedAt,
        public int $remainingCount,
    ) {
    }
}
