<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\ApiKeyCredential;

use Shared\Application\Result\ResultInterface;

final readonly class ApiKeyCredentialResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $label,
        public string $keyId,
        public string $secretHash,
        public \DateTimeImmutable $issuedAt,
        public bool $revoked,
        public ?\DateTimeImmutable $revokedAt,
        public bool $identityAuthenticatable,
    ) {
    }
}
