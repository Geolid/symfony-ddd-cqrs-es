<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Entity\BackupCode;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialBackupCodeConsumed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialBackupCodesRegenerated;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialIssued;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.authentication.totp_credential')]
final class TotpCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) TotpCredentialId $id;
    private string $identityId;
    private bool $revoked;
    /** @var list<BackupCode> */
    private array $backupCodes;

    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public static function issue(
        TotpCredentialId $id,
        string $identityId,
        #[\SensitiveParameter]
        string $secret,
        TotpCipherInterface $cipher,
        #[\SensitiveParameter]
        array $plainBackupCodes,
        TotpBackupCodeHasherInterface $backupCodeHasher,
        \DateTimeImmutable $issuedAt,
    ): self {
        $self = new self();
        $self->recordThat(new TotpCredentialIssued(
            id: $id,
            identityId: $identityId,
            encryptedSecret: $cipher->encrypt($secret),
            backupCodes: self::hashBackupCodes($plainBackupCodes, $backupCodeHasher),
            issuedAt: $issuedAt,
        ));

        return $self;
    }

    /**
     * @throws TotpCredentialOwnedByAnotherIdentityException
     */
    public function revoke(string $identityId, \DateTimeImmutable $revokedAt): void
    {
        $this->assertOwnedBy($identityId);

        if ($this->revoked) {
            return;
        }

        $this->recordThat(new TotpCredentialRevoked(
            id: $this->id,
            revokedAt: $revokedAt,
        ));
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     *
     * @throws TotpCredentialOwnedByAnotherIdentityException
     */
    public function regenerateBackupCodes(
        string $identityId,
        #[\SensitiveParameter]
        array $plainBackupCodes,
        TotpBackupCodeHasherInterface $backupCodeHasher,
        \DateTimeImmutable $regeneratedAt,
    ): void {
        $this->assertOwnedBy($identityId);

        $this->recordThat(new TotpCredentialBackupCodesRegenerated(
            id: $this->id,
            backupCodes: self::hashBackupCodes($plainBackupCodes, $backupCodeHasher),
            regeneratedAt: $regeneratedAt,
        ));
    }

    /**
     * @throws InvalidBackupCodeException
     */
    public function consumeBackupCode(
        #[\SensitiveParameter]
        string $code,
        TotpBackupCodeHasherInterface $backupCodeHasher,
        \DateTimeImmutable $consumedAt,
    ): void {
        foreach ($this->backupCodes as $backupCode) {
            if (!$backupCode->isConsumed() && $backupCodeHasher->verify($code, $backupCode->hashedCode)) {
                $this->recordThat(new TotpCredentialBackupCodeConsumed(
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
    private function applyIssued(TotpCredentialIssued $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->revoked = false;
        $this->backupCodes = $event->backupCodes;
    }

    #[Apply]
    private function applyRevoked(TotpCredentialRevoked $event): void
    {
        $this->revoked = true;
    }

    #[Apply]
    private function applyBackupCodesRegenerated(TotpCredentialBackupCodesRegenerated $event): void
    {
        $this->backupCodes = $event->backupCodes;
    }

    #[Apply]
    private function applyBackupCodeConsumed(TotpCredentialBackupCodeConsumed $event): void
    {
        $this->backupCodes = array_map(
            static fn (BackupCode $backupCode): BackupCode => $backupCode->hashedCode === $event->hashedCode
                ? $backupCode->consumed($event->consumedAt)
                : $backupCode,
            $this->backupCodes,
        );
    }

    /**
     * @throws TotpCredentialOwnedByAnotherIdentityException
     */
    private function assertOwnedBy(string $identityId): void
    {
        if ($this->identityId !== $identityId) {
            throw TotpCredentialOwnedByAnotherIdentityException::forId($this->id);
        }
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     *
     * @return list<BackupCode>
     */
    private static function hashBackupCodes(array $plainBackupCodes, TotpBackupCodeHasherInterface $backupCodeHasher): array
    {
        return array_map(
            static fn (string $code): BackupCode => new BackupCode($backupCodeHasher->hash($code)),
            $plainBackupCodes,
        );
    }
}
