<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\BackupCodeIssuance;

use Iam\Authentication\Application\BackupCodeIssuance\BackupCodeRegeneratorInterface;
use Iam\Authentication\Application\BackupCodeIssuance\Exception\BackupCodeCredentialNotIssuedException;
use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class BackupCodeRegeneratorTest extends AbstractIntegrationTestCase
{
    private BackupCodeHasherInterface $backupCodeHasher;
    private BackupCodeRegeneratorInterface $regenerator;
    private BackupCodeCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupCodeHasher = $this->service(BackupCodeHasherInterface::class);
        $this->regenerator = $this->service(BackupCodeRegeneratorInterface::class);
        $this->verifier = $this->service(BackupCodeCredentialVerifierInterface::class);
    }

    #[Test]
    public function itRegenerates(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $newBackupCodes = $this->regenerator->regenerateFor($builder['identityId']);

        // Then
        self::assertNotEmpty($newBackupCodes);
        self::assertFalse($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]));
        self::assertTrue($this->verifier->verify($builder['identityId'], $newBackupCodes[0]));
    }

    #[Test]
    public function itFailsWhenNotIssued(): void
    {
        // Then
        $this->expectException(BackupCodeCredentialNotIssuedException::class);

        // When
        $this->regenerator->regenerateFor(BackupCodeCredentialBuilder::sample('identityId'));
    }
}
