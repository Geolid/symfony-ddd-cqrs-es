<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

final class DbalBackupCodeCredentialFinderTest extends AbstractIntegrationTestCase
{
    private BackupCodeCredentialFinderInterface $finder;
    private FakeBackupCodeHasher $backupCodeHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(BackupCodeCredentialFinderInterface::class);
        $this->backupCodeHasher = new FakeBackupCodeHasher();
    }

    #[Test]
    public function itFindsByIdentity(): void
    {
        // Given
        $other = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->backupCodeHasher)->create();
        $plainBackupCodes = faker()->backupCodes();

        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentityOrNull($credential->identityId);

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->identityId, $result->identityId);
        self::assertSameDate($credential->generatedAt, $result->generatedAt);
        self::assertNull($result->regeneratedAt);
        self::assertSame(\count($plainBackupCodes), $result->remainingCount);
    }

    #[Test]
    public function itFindsNothingWhenNotGenerated(): void
    {
        // When
        $result = $this->finder->ofIdentityOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }
}
