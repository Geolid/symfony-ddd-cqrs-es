<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\BackupCodeRegeneration;

use Iam\Authentication\Application\BackupCodeRegeneration\BackupCodeRegeneratorInterface;
use Iam\Authentication\Application\BackupCodeRegeneration\Exception\BackupCodeCredentialNotGeneratedException;
use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $plainBackupCodes = [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))];
        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

        // When
        $newBackupCodes = $this->regenerator->regenerateFor($credential->identityId);

        // Then
        $backupCodeCount = self::getContainer()->getParameter('iam.authentication.backup_code_count');
        self::assertIsInt($backupCodeCount);
        self::assertCount($backupCodeCount, $newBackupCodes);
        self::assertFalse($this->verifier->verify($credential->identityId, $plainBackupCodes[0]));
        self::assertTrue($this->verifier->verify($credential->identityId, $newBackupCodes[0]));
    }

    #[Test]
    public function itFailsWhenNotGenerated(): void
    {
        // Then
        $this->expectException(BackupCodeCredentialNotGeneratedException::class);

        // When
        $this->regenerator->regenerateFor(Uuid::uuid7()->toString());
    }
}
