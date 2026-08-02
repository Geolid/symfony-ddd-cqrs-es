<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\IssueApiTokenCredential;

use Shared\Application\Command\CommandInterface;

final readonly class IssueApiTokenCredential implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $identifier,
        public string $secret,
        public string $expiresAt,
    ) {
    }
}
