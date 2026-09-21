<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Event;

use Iam\Authentication\Domain\TotpCredential\Entity\BackupCode;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.totp_credential.backup_codes_regenerated')]
final readonly class TotpCredentialBackupCodesRegenerated
{
    /**
     * @param list<BackupCode> $backupCodes
     */
    public function __construct(
        public TotpCredentialId $id,
        public array $backupCodes,
        public \DateTimeImmutable $regeneratedAt,
    ) {
    }
}
