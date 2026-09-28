<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class BackupCodeCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private BackupCodeHasherInterface $backupCodeHasher;
    private BackupCodeCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupCodeHasher = $this->service(BackupCodeHasherInterface::class);
        $this->verifier = $this->service(BackupCodeCredentialVerifierInterface::class);
    }

    #[Test]
    public function itAccepts(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['identityId'], 'INVALIDCODE');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenNotIssued(): void
    {
        // When
        $verified = $this->verifier->verify(BackupCodeCredentialBuilder::sample('identityId'), 'INVALIDCODE');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenAlreadyConsumed(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher)->consumed();
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['identityId'], $builder['consumedBackupCode']);

        // Then
        self::assertFalse($verified);
    }
}
