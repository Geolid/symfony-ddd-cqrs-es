<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiToken;

use Shared\Application\Query\Result\ResultInterface;

final readonly class ApiTokenCredentialResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $identifier,
        public string $secretHash,
        public bool $revoked,
    ) {
    }
}
