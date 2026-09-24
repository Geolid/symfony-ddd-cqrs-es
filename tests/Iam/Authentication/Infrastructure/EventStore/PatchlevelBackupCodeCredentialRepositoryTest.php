<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\Repository\BackupCodeCredentialRepositoryInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelBackupCodeCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private BackupCodeCredentialRepositoryInterface $repository;
    private FakeBackupCodeHasher $backupCodeHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(BackupCodeCredentialRepositoryInterface::class);
        $this->backupCodeHasher = new FakeBackupCodeHasher();
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $credential = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher)->create();

        // When
        $this->repository->save($credential);
        $loaded = $this->repository->load($credential->id);

        // Then
        self::assertSame($credential->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(BackupCodeCredentialNotFoundException::class);

        // When
        $this->repository->load(BackupCodeCredentialId::forIdentity(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $credential = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->repository->save($credential);

        // When
        $exists = $this->repository->has($credential->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(BackupCodeCredentialId::forIdentity(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
