<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\TotpCredential;

final readonly class TotpCredentialResult
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $encryptedSecret,
        public \DateTimeImmutable $issuedAt,
        public bool $revoked,
        public ?\DateTimeImmutable $revokedAt,
    ) {
    }
}
