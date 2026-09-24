<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\IssueBackupCodeCredential;

use Shared\Application\Command\CommandInterface;

final readonly class IssueBackupCodeCredential implements CommandInterface
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
