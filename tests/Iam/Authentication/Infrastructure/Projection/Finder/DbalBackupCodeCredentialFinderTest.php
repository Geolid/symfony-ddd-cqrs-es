<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeBackupCodeHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $other = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher)->create();

        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentityOrNull($builder['identityId']);

        // Then
        self::assertNotNull($result);
        self::assertSame($builder['identityId'], $result->identityId);
    }

    #[Test]
    public function itFindsNothingWhenNotIssued(): void
    {
        // When
        $result = $this->finder->ofIdentityOrNull(BackupCodeCredentialBuilder::sample('identityId'));

        // Then
        self::assertNull($result);
    }
}
