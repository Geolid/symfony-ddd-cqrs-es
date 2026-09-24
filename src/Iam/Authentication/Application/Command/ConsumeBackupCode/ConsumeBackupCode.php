<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ConsumeBackupCode;

use Shared\Application\Command\CommandInterface;

final readonly class ConsumeBackupCode implements CommandInterface
{
    public function __construct(
        public string $identityId,
        #[\SensitiveParameter]
        public string $code,
    ) {
    }
}
