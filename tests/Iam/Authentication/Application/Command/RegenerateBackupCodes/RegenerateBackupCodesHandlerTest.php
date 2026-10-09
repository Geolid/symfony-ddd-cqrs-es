<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RegenerateBackupCodes;

use Iam\Authentication\Application\Command\RegenerateBackupCodes\RegenerateBackupCodes;
use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

final class RegenerateBackupCodesHandlerTest extends AbstractIntegrationTestCase
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
    public function itRegenerates(): void
    {
        // Given
        $plainBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

        $newBackupCodes = faker()->backupCodes();

        // When
        $this->dispatch(new RegenerateBackupCodes($credential->identityId, $newBackupCodes));

        // Then
        self::assertFalse($this->verifier->verify($credential->identityId, $plainBackupCodes[0]));
        self::assertTrue($this->verifier->verify($credential->identityId, $newBackupCodes[0]));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(BackupCodeCredentialNotFoundException::class);

        // When
        $this->dispatch(new RegenerateBackupCodes(
            Uuid::uuid7()->toString(),
            faker()->backupCodes(),
        ));
    }
}
