<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\BackupCodeCredential;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Entity\BackupCode;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialConsumed;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialIssued;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialRegenerated;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class BackupCodeCredentialTest extends AggregateRootTestCase
{
    private BackupCodeCredentialId $id;
    private string $identityId;
    /** @var list<non-empty-string> */
    private array $plainBackupCodes;
    private \DateTimeImmutable $issuedAt;
    private BackupCodeHasherInterface $backupCodeHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = BackupCodeCredentialBuilder::sample('identityId');
        $this->id = BackupCodeCredentialId::forIdentity($this->identityId);
        $this->plainBackupCodes = BackupCodeCredentialBuilder::sample('plainBackupCodes');
        $this->issuedAt = BackupCodeCredentialBuilder::sample('issuedAt');
        $this->backupCodeHasher = new FakeBackupCodeHasher();
    }

    #[Test]
    public function itIssues(): void
    {
        $this
            ->given()
            ->when(fn (): BackupCodeCredential => BackupCodeCredential::issue(
                $this->id,
                $this->identityId,
                $this->plainBackupCodes,
                $this->backupCodeHasher,
                $this->issuedAt,
            ))
            ->then($this->issued());
    }

    #[Test]
    public function itRegenerates(): void
    {
        $regeneratedBackupCodes = BackupCodeCredentialBuilder::sample('regeneratedBackupCodes');
        $regeneratedAt = BackupCodeCredentialBuilder::sample('regeneratedAt');

        $this
            ->given($this->issued())
            ->when(fn (BackupCodeCredential $credential) => $credential->regenerate(
                $regeneratedBackupCodes,
                $this->backupCodeHasher,
                $regeneratedAt,
            ))
            ->then(new BackupCodeCredentialRegenerated(
                $this->id,
                $this->hashedBackupCodes($regeneratedBackupCodes),
                $regeneratedAt,
            ));
    }

    #[Test]
    public function itConsumes(): void
    {
        $consumedAt = BackupCodeCredentialBuilder::sample('consumedAt');

        $this
            ->given($this->issued())
            ->when(fn (BackupCodeCredential $credential) => $credential->consume(
                $this->plainBackupCodes[0],
                $this->backupCodeHasher,
                $consumedAt,
            ))
            ->then(new BackupCodeCredentialConsumed(
                $this->id,
                $this->backupCodeHasher->hash($this->plainBackupCodes[0]),
                $consumedAt,
            ));
    }

    #[Test]
    public function itCannotConsumeWhenInvalid(): void
    {
        $this
            ->given($this->issued())
            ->when(fn (BackupCodeCredential $credential) => $credential->consume(
                'INVALIDCODE',
                $this->backupCodeHasher,
                BackupCodeCredentialBuilder::sample('consumedAt'),
            ))
            ->expectsException(InvalidBackupCodeException::class);
    }

    #[Test]
    public function itCannotConsumeWhenAlreadyConsumed(): void
    {
        $consumedAt = BackupCodeCredentialBuilder::sample('consumedAt');

        $this
            ->given(
                $this->issued(),
                new BackupCodeCredentialConsumed(
                    $this->id,
                    $this->backupCodeHasher->hash($this->plainBackupCodes[0]),
                    $consumedAt,
                ),
            )
            ->when(fn (BackupCodeCredential $credential) => $credential->consume(
                $this->plainBackupCodes[0],
                $this->backupCodeHasher,
                BackupCodeCredentialBuilder::sample('consumedAt'),
            ))
            ->expectsException(InvalidBackupCodeException::class);
    }

    protected function aggregateClass(): string
    {
        return BackupCodeCredential::class;
    }

    private function issued(): BackupCodeCredentialIssued
    {
        return new BackupCodeCredentialIssued(
            $this->id,
            $this->identityId,
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
