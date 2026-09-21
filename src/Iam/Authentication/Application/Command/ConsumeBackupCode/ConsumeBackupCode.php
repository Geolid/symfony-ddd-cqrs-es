<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ConsumeBackupCode;

use Shared\Application\Command\CommandInterface;

final readonly class ConsumeBackupCode implements CommandInterface
{
    public function __construct(
        public string $id,
        #[\SensitiveParameter]
        public string $code,
    ) {
    }
}
