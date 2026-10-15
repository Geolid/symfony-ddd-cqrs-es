<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\BackupCodeRegeneration;

use Iam\Authentication\Application\BackupCodeRegeneration\BackupCodeRegenerator;
use Iam\Authentication\Application\BackupCodeRegeneration\BackupCodeRegeneratorInterface;
use Iam\Authentication\Application\BackupCodeRegeneration\Exception\BackupCodeCredentialNotGeneratedException;
use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

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
        $plainBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);
        $backupCodeCount = 3;
        $regenerator = new BackupCodeRegenerator(
            $this->service(BackupCodeCredentialFinderInterface::class),
            $this->service(BackupCodeGeneratorInterface::class),
            $this->service(CommandBusInterface::class),
            $backupCodeCount,
        );

        // When
        $newBackupCodes = $regenerator->regenerateFor($credential->identityId);

        // Then
        self::assertCount($backupCodeCount, $newBackupCodes);
        self::assertFalse($this->verifier->verify($credential->identityId, $plainBackupCodes[0]));
        self::assertTrue($this->verifier->verify($credential->identityId, $newBackupCodes[0]));
    }

    #[Test]
    public function itFailsWhenNotGenerated(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(BackupCodeCredentialNotGeneratedException::class);

        // When
        $this->regenerator->regenerateFor($identityId);
    }
}
