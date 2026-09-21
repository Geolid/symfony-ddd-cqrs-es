<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TotpCredential;

use Iam\Authentication\Domain\TotpCredential\Entity\BackupCode;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialBackupCodeConsumed;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialBackupCodesRegenerated;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialIssued;
use Iam\Authentication\Domain\TotpCredential\Event\TotpCredentialRevoked;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpBackupCodeHasher;
use Iam\Tests\Authentication\Support\Double\FakeTotpCipher;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class TotpCredentialTest extends AggregateRootTestCase
{
    private TotpCredentialId $id;
    private string $identityId;
    private string $secret;
    /** @var list<non-empty-string> */
    private array $plainBackupCodes;
    private \DateTimeImmutable $issuedAt;
    private TotpCipherInterface $cipher;
    private TotpBackupCodeHasherInterface $backupCodeHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = TotpCredentialId::fromString(Uuid::uuid7()->toString());
        $this->identityId = TotpCredentialBuilder::sample('identityId');
        $this->secret = TotpCredentialBuilder::sample('secret');
        $this->plainBackupCodes = TotpCredentialBuilder::sample('plainBackupCodes');
        $this->issuedAt = TotpCredentialBuilder::sample('issuedAt');
        $this->cipher = new FakeTotpCipher();
        $this->backupCodeHasher = new FakeTotpBackupCodeHasher();
    }

    #[Test]
    public function itIssues(): void
    {
        $this
            ->given()
            ->when(fn (): TotpCredential => TotpCredential::issue(
                $this->id,
                $this->identityId,
                $this->secret,
                $this->cipher,
                $this->plainBackupCodes,
                $this->backupCodeHasher,
                $this->issuedAt,
            ))
            ->then($this->issued());
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given($this->issued())
            ->when(fn (TotpCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then(new TotpCredentialRevoked($this->id, $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = TotpCredentialBuilder::sample('revokedAt');

        $this
            ->given(
                $this->issued(),
                new TotpCredentialRevoked($this->id, $revokedAt),
            )
            ->when(fn (TotpCredential $credential) => $credential->revoke($this->identityId, $revokedAt))
            ->then();
    }

    #[Test]
    public function itCannotRevokeWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = TotpCredentialBuilder::sample('identityId');

        $this
            ->given($this->issued())
            ->when(static fn (TotpCredential $credential) => $credential->revoke($anotherIdentityId, TotpCredentialBuilder::sample('revokedAt')))
            ->expectsException(TotpCredentialOwnedByAnotherIdentityException::class);
    }

    #[Test]
    public function itRegeneratesBackupCodes(): void
    {
        $regeneratedBackupCodes = TotpCredentialBuilder::sample('regeneratedBackupCodes');
        $regeneratedAt = TotpCredentialBuilder::sample('regeneratedAt');

        $this
            ->given($this->issued())
            ->when(fn (TotpCredential $credential) => $credential->regenerateBackupCodes(
                $this->identityId,
                $regeneratedBackupCodes,
                $this->backupCodeHasher,
                $regeneratedAt,
            ))
            ->then(new TotpCredentialBackupCodesRegenerated(
                $this->id,
                $this->hashedBackupCodes($regeneratedBackupCodes),
                $regeneratedAt,
            ));
    }

    #[Test]
    public function itCannotRegenerateBackupCodesWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = TotpCredentialBuilder::sample('identityId');

        $this
            ->given($this->issued())
            ->when(fn (TotpCredential $credential) => $credential->regenerateBackupCodes(
                $anotherIdentityId,
                TotpCredentialBuilder::sample('regeneratedBackupCodes'),
                $this->backupCodeHasher,
                TotpCredentialBuilder::sample('regeneratedAt'),
            ))
            ->expectsException(TotpCredentialOwnedByAnotherIdentityException::class);
    }

    #[Test]
    public function itConsumesBackupCode(): void
    {
        $consumedAt = TotpCredentialBuilder::sample('consumedAt');

        $this
            ->given($this->issued())
            ->when(fn (TotpCredential $credential) => $credential->consumeBackupCode(
                $this->plainBackupCodes[0],
                $this->backupCodeHasher,
                $consumedAt,
            ))
            ->then(new TotpCredentialBackupCodeConsumed(
                $this->id,
                $this->backupCodeHasher->hash($this->plainBackupCodes[0]),
                $consumedAt,
            ));
    }

    #[Test]
    public function itCannotConsumeBackupCodeWhenInvalid(): void
    {
        $this
            ->given($this->issued())
            ->when(fn (TotpCredential $credential) => $credential->consumeBackupCode(
                'INVALIDCODE',
                $this->backupCodeHasher,
                TotpCredentialBuilder::sample('consumedAt'),
            ))
            ->expectsException(InvalidBackupCodeException::class);
    }

    #[Test]
    public function itCannotConsumeBackupCodeWhenAlreadyConsumed(): void
    {
        $consumedAt = TotpCredentialBuilder::sample('consumedAt');

        $this
            ->given(
                $this->issued(),
                new TotpCredentialBackupCodeConsumed(
                    $this->id,
                    $this->backupCodeHasher->hash($this->plainBackupCodes[0]),
                    $consumedAt,
                ),
            )
            ->when(fn (TotpCredential $credential) => $credential->consumeBackupCode(
                $this->plainBackupCodes[0],
                $this->backupCodeHasher,
                TotpCredentialBuilder::sample('consumedAt'),
            ))
            ->expectsException(InvalidBackupCodeException::class);
    }

    protected function aggregateClass(): string
    {
        return TotpCredential::class;
    }

    private function issued(): TotpCredentialIssued
    {
        return new TotpCredentialIssued(
            $this->id,
            $this->identityId,
            $this->cipher->encrypt($this->secret),
            $this->hashedBackupCodes($this->plainBackupCodes),
            $this->issuedAt,
        );
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     *
     * @return list<BackupCode>
     */
    private function hashedBackupCodes(array $plainBackupCodes): array
    {
        return array_map(
            fn (string $code): BackupCode => new BackupCode($this->backupCodeHasher->hash($code)),
            $plainBackupCodes,
        );
    }
}
