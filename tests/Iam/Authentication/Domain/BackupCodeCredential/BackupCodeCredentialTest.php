<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\BackupCodeCredential;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Entity\BackupCode;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialConsumed;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialGenerated;
use Iam\Authentication\Domain\BackupCodeCredential\Event\BackupCodeCredentialRegenerated;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Clock\Clock;

final class BackupCodeCredentialTest extends AggregateRootTestCase
{
    private BackupCodeCredentialId $id;
    private string $identityId;
    /** @var list<non-empty-string> */
    private array $plainBackupCodes;
    private \DateTimeImmutable $generatedAt;
    private BackupCodeHasherInterface $backupCodeHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = Uuid::uuid7()->toString();
        $this->id = BackupCodeCredentialId::forIdentity($this->identityId);
        $this->plainBackupCodes = [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))];
        $this->generatedAt = Clock::get()->now();
        $this->backupCodeHasher = new FakeBackupCodeHasher();
    }

    #[Test]
    public function itGenerates(): void
    {
        $this
            ->given()
            ->when(fn (): BackupCodeCredential => BackupCodeCredential::generate(
                $this->id,
                $this->identityId,
                $this->plainBackupCodes,
                $this->backupCodeHasher,
                $this->generatedAt,
            ))
            ->then($this->generated());
    }

    #[Test]
    public function itRegenerates(): void
    {
        $regeneratedBackupCodes = [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))];
        $regeneratedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given($this->generated())
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
        $consumedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given($this->generated())
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
            ->given($this->generated())
            ->when(fn (BackupCodeCredential $credential) => $credential->consume(
                'INVALIDCODE',
                $this->backupCodeHasher,
                Clock::get()->now()->modify('+1 day'),
            ))
            ->expectsException(InvalidBackupCodeException::class);
    }

    #[Test]
    public function itCannotConsumeWhenAlreadyConsumed(): void
    {
        $consumedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given(
                $this->generated(),
                new BackupCodeCredentialConsumed(
                    $this->id,
                    $this->backupCodeHasher->hash($this->plainBackupCodes[0]),
                    $consumedAt,
                ),
            )
            ->when(fn (BackupCodeCredential $credential) => $credential->consume(
                $this->plainBackupCodes[0],
                $this->backupCodeHasher,
                Clock::get()->now()->modify('+1 day'),
            ))
            ->expectsException(InvalidBackupCodeException::class);
    }

    protected function aggregateClass(): string
    {
        return BackupCodeCredential::class;
    }

    private function generated(): BackupCodeCredentialGenerated
    {
        return new BackupCodeCredentialGenerated(
            $this->id,
            $this->identityId,
            $this->hashedBackupCodes($this->plainBackupCodes),
            $this->generatedAt,
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
