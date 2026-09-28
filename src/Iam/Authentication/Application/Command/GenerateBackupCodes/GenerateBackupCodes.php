<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\GenerateBackupCodes;

use Shared\Application\Command\CommandInterface;

final readonly class GenerateBackupCodes implements CommandInterface
{
    /**
     * @param list<non-empty-string> $backupCodes
     */
    public function __construct(
        public string $identityId,
        #[\SensitiveParameter]
        public array $backupCodes,
    ) {
    }
}
