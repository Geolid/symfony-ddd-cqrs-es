<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiTokenCredential;

use Shared\Application\Result\ResultInterface;

final readonly class ApiTokenCredentialResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $identifier,
        public string $hash,
        public bool $revoked,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
