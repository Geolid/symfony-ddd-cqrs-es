<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalBackupCodeCredentialProjector;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{issued_at: string, regenerated_at: string|null, remaining_count: int}
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
    public function itProjectsOnBackupCodeCredentialIssued(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($builder['identityId']);
        self::assertNotFalse($row);
        self::assertSame($builder['issuedAt']->format(self::DATE_FORMAT), $row['issued_at']);
        self::assertNull($row['regenerated_at']);
        self::assertSame(\count($builder['plainBackupCodes']), (int) $row['remaining_count']);
    }

    #[Test]
    public function itProjectsOnBackupCodeCredentialRegenerated(): void
    {
        // Given
        $otherBuilder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $other = $otherBuilder->create();

        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher)->regenerated();
        $credential = $builder->create();

        // When
        $this->store($other, $credential);

        // Then
        $row = $this->fetchRow($builder['identityId']);
        self::assertNotFalse($row);
        self::assertSame($builder['regeneratedAt']->format(self::DATE_FORMAT), $row['regenerated_at']);
        self::assertSame(\count($builder['regeneratedBackupCodes']), (int) $row['remaining_count']);

        $otherRow = $this->fetchRow($otherBuilder['identityId']);
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['regenerated_at']);
        self::assertSame(\count($otherBuilder['plainBackupCodes']), (int) $otherRow['remaining_count']);
    }

    #[Test]
    public function itProjectsOnBackupCodeCredentialConsumed(): void
    {
        // Given
        $otherBuilder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $other = $otherBuilder->create();

        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher)->consumed();
        $credential = $builder->create();

        // When
        $this->store($other, $credential);

        // Then
        $row = $this->fetchRow($builder['identityId']);
        self::assertNotFalse($row);
        self::assertSame(\count($builder['plainBackupCodes']) - 1, (int) $row['remaining_count']);

        $otherRow = $this->fetchRow($otherBuilder['identityId']);
        self::assertNotFalse($otherRow);
        self::assertSame(\count($otherBuilder['plainBackupCodes']), (int) $otherRow['remaining_count']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $otherBuilder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $other = $otherBuilder->create();
        $this->store($other);

        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $credential = BackupCodeCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withBackupCodeHasher($this->backupCodeHasher)
            ->create();

        // When
        $this->store($credential, $identity);

        // Then
        self::assertFalse($this->fetchRow($identity->id->toString()));
        self::assertNotFalse($this->fetchRow($otherBuilder['identityId']));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $identityId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT issued_at, regenerated_at, remaining_count FROM %s WHERE identity_id = :identityId', DbalBackupCodeCredentialProjector::TABLE),
            ['identityId' => $identityId],
        );
    }
}
