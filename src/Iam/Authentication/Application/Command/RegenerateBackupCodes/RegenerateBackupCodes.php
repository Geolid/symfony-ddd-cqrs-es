<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RegenerateBackupCodes;

use Shared\Application\Command\CommandInterface;

final readonly class RegenerateBackupCodes implements CommandInterface
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
