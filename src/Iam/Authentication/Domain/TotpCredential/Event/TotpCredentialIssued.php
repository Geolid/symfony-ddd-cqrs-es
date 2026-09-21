<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\Event;

use Iam\Authentication\Domain\TotpCredential\Entity\BackupCode;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.totp_credential.issued')]
final readonly class TotpCredentialIssued
{
    /**
     * @param list<BackupCode> $backupCodes
     */
    public function __construct(
        public TotpCredentialId $id,
        public string $identityId,
        public string $encryptedSecret,
        public array $backupCodes,
        public \DateTimeImmutable $issuedAt,
    ) {
    }
}
