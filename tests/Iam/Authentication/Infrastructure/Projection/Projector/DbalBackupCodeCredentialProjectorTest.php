<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalBackupCodeCredentialProjector;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Row array{generated_at: string, regenerated_at: string|null, remaining_count: int}
 */
final class DbalBackupCodeCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private FakeBackupCodeHasher $backupCodeHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupCodeHasher = new FakeBackupCodeHasher();
    }

    #[Test]
    public function itProjectsOnBackupCodeCredentialGenerated(): void
    {
        // Given
        $plainBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->identityId);
        self::assertNotFalse($row);
        self::assertSame($credential->generatedAt->format(self::DATE_FORMAT), $row['generated_at']);
        self::assertNull($row['regenerated_at']);
        self::assertSame(\count($plainBackupCodes), (int) $row['remaining_count']);
    }

    #[Test]
    public function itProjectsOnBackupCodeCredentialRegenerated(): void
    {
        // Given
        $otherPlainBackupCodes = faker()->backupCodes();
        $other = BackupCodeCredentialFactory::new()->withPlainBackupCodes($otherPlainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();

        $regeneratedBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->backupCodeHasher)->regenerated($regeneratedBackupCodes)->create();

        // When
        $this->store($other, $credential);

        // Then
        $row = $this->fetchRow($credential->identityId);
        self::assertNotFalse($row);
        self::assertSame($credential->regeneratedAt?->format(self::DATE_FORMAT), $row['regenerated_at']);
        self::assertSame(\count($regeneratedBackupCodes), (int) $row['remaining_count']);

        $otherRow = $this->fetchRow($other->identityId);
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['regenerated_at']);
        self::assertSame(\count($otherPlainBackupCodes), (int) $otherRow['remaining_count']);
    }

    #[Test]
    public function itProjectsOnBackupCodeCredentialConsumed(): void
    {
        // Given
        $otherPlainBackupCodes = faker()->backupCodes();
        $other = BackupCodeCredentialFactory::new()->withPlainBackupCodes($otherPlainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $plainBackupCodes = faker()->backupCodes();

        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->consumed()->create();

        // When
        $this->store($other, $credential);

        // Then
        $row = $this->fetchRow($credential->identityId);
        self::assertNotFalse($row);
        self::assertSame(\count($plainBackupCodes) - 1, (int) $row['remaining_count']);

        $otherRow = $this->fetchRow($other->identityId);
        self::assertNotFalse($otherRow);
        self::assertSame(\count($otherPlainBackupCodes), (int) $otherRow['remaining_count']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($other);

        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $credential = BackupCodeCredentialFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withBackupCodeHasher($this->backupCodeHasher)
            ->create();

        // When
        $this->store($credential, $identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
        self::assertNotFalse($this->fetchRow($other->identityId));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $identityId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT generated_at, regenerated_at, remaining_count FROM %s WHERE identity_id = :identityId', DbalBackupCodeCredentialProjector::TABLE),
            ['identityId' => $identityId],
        );
    }
}
