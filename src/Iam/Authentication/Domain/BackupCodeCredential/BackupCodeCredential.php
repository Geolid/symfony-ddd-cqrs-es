<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential;

use Iam\Authentication\Domain\BackupCodeCredential\Entity\BackupCode;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialConsumed;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialIssued;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialRegenerated;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.authentication.backup_code_credential')]
final class BackupCodeCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) BackupCodeCredentialId $id;
    /** @var list<BackupCode> */
    private array $backupCodes;

    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public static function issue(
        BackupCodeCredentialId $id,
        string $identityId,
        #[\SensitiveParameter]
        array $plainBackupCodes,
        BackupCodeHasherInterface $backupCodeHasher,
        \DateTimeImmutable $issuedAt,
    ): self {
        $self = new self();
        $self->recordThat(new BackupCodeCredentialIssued(
            id: $id,
            identityId: $identityId,
            backupCodes: self::hashBackupCodes($plainBackupCodes, $backupCodeHasher),
            issuedAt: $issuedAt,
        ));

        return $self;
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public function regenerate(
        #[\SensitiveParameter]
        array $plainBackupCodes,
        BackupCodeHasherInterface $backupCodeHasher,
        \DateTimeImmutable $regeneratedAt,
    ): void {
        $this->recordThat(new BackupCodeCredentialRegenerated(
            id: $this->id,
            backupCodes: self::hashBackupCodes($plainBackupCodes, $backupCodeHasher),
            regeneratedAt: $regeneratedAt,
        ));
    }

    /**
     * @throws InvalidBackupCodeException
     */
    public function consume(
        #[\SensitiveParameter]
        string $code,
        BackupCodeHasherInterface $backupCodeHasher,
        \DateTimeImmutable $consumedAt,
    ): void {
        foreach ($this->backupCodes as $backupCode) {
            if (!$backupCode->isConsumed() && $backupCodeHasher->verify($code, $backupCode->hashedCode)) {
                $this->recordThat(new BackupCodeCredentialConsumed(
                    id: $this->id,
                    hashedCode: $backupCode->hashedCode,
                    consumedAt: $consumedAt,
                ));

                return;
            }
        }

        throw InvalidBackupCodeException::forId($this->id);
    }

    #[Apply]
    private function applyIssued(BackupCodeCredentialIssued $event): void
    {
        $this->id = $event->id;
        $this->backupCodes = $event->backupCodes;
    }

    #[Apply]
    private function applyRegenerated(BackupCodeCredentialRegenerated $event): void
    {
        $this->backupCodes = $event->backupCodes;
    }

    #[Apply]
    private function applyConsumed(BackupCodeCredentialConsumed $event): void
    {
        $this->backupCodes = array_map(
            static fn (BackupCode $backupCode): BackupCode => $backupCode->hashedCode === $event->hashedCode
                ? $backupCode->consumed($event->consumedAt)
                : $backupCode,
            $this->backupCodes,
        );
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     *
     * @return list<BackupCode>
     */
    private static function hashBackupCodes(array $plainBackupCodes, BackupCodeHasherInterface $backupCodeHasher): array
    {
        return array_map(
            static fn (string $code): BackupCode => new BackupCode($backupCodeHasher->hash($code)),
            $plainBackupCodes,
        );
    }
}
