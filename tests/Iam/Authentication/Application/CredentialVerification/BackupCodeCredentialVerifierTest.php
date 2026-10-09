<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

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
        $plainBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($credential->identityId, $plainBackupCodes[0]);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $credential = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($credential->identityId, 'INVALIDCODE');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenNotGenerated(): void
    {
        // When
        $verified = $this->verifier->verify(Uuid::uuid7()->toString(), 'INVALIDCODE');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenAlreadyConsumed(): void
    {
        // Given
        $plainBackupCodes = faker()->backupCodes();
        $credential = BackupCodeCredentialFactory::new()->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->consumed($plainBackupCodes[0])->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($credential->identityId, $plainBackupCodes[0]);

        // Then
        self::assertFalse($verified);
    }
}
