<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\TotpCredential;

use Iam\Authentication\Application\TotpCredentialStatus;

final readonly class TotpCredentialResult
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $encryptedSecret,
        public \DateTimeImmutable $enrolledAt,
        public TotpCredentialStatus $status,
        public ?\DateTimeImmutable $confirmedAt,
        public ?\DateTimeImmutable $revokedAt,
    ) {
    }
}
