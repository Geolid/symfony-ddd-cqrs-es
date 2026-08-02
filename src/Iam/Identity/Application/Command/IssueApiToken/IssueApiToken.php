<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\IssueApiToken;

use Shared\Application\Command\CommandInterface;

final readonly class IssueApiToken implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $identifier,
        public string $secretHash,
    ) {
    }
}
