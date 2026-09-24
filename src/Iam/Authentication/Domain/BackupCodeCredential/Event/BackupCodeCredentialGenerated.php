<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Event;

use Iam\Authentication\Domain\BackupCodeCredential\Entity\BackupCode;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.backup_code_credential.generated')]
final readonly class BackupCodeCredentialGenerated
{
    /**
     * @param list<BackupCode> $backupCodes
     */
    public function __construct(
        public BackupCodeCredentialId $id,
        public string $identityId,
        public array $backupCodes,
        public \DateTimeImmutable $generatedAt,
    ) {
    }
}
