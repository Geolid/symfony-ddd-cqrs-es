<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Event;

use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.backup_code_credential.consumed')]
final readonly class BackupCodeCredentialConsumed
{
    public function __construct(
        public BackupCodeCredentialId $id,
        public string $hashedCode,
        public \DateTimeImmutable $consumedAt,
    ) {
    }
}
