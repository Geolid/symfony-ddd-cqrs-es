<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Entity\BackupCode;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialAlreadyExistsException;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\Repository\BackupCodeCredentialRepositoryInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

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
        $regeneratedBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()
            ->withBackupCodeHasher($this->backupCodeHasher)
            ->regenerated($regeneratedBackupCodes)
            ->consumed($regeneratedBackupCodes[0])
            ->create();

        // When
        $this->repository->save($credential);
        $loaded = $this->repository->load($credential->id);

        // Then
        self::assertSame($this->propertiesOf($credential), $this->propertiesOf($loaded));
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $credential = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);
        $duplicate = BackupCodeCredentialFactory::new()->withIdentityId($credential->identityId)->withBackupCodeHasher($this->backupCodeHasher)->create();

        // Then
        $this->expectException(BackupCodeCredentialAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
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
        $credential = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

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

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(BackupCodeCredential $credential): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $credential->id->toString(),
            'identityId' => $credential->identityId,
            'backupCodes' => array_map(
                static fn (BackupCode $backupCode): array => ['hashedCode' => $backupCode->hashedCode, 'consumedAt' => $atom($backupCode->consumedAt)],
                $credential->backupCodes,
            ),
            'generatedAt' => $atom($credential->generatedAt),
            'regeneratedAt' => $atom($credential->regeneratedAt),
        ];
    }
}
