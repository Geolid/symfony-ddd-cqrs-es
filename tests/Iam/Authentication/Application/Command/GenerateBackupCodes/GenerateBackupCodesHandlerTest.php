<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\GenerateBackupCodes;

use Iam\Authentication\Application\Command\GenerateBackupCodes\GenerateBackupCodes;
use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

final class GenerateBackupCodesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGenerates(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $backupCodes = faker()->backupCodes();

        // When
        $this->dispatch(new GenerateBackupCodes($identityId, $backupCodes));

        // Then
        $result = $this->service(BackupCodeCredentialFinderInterface::class)->ofIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame($identityId, $result->identityId);

        $verified = $this->service(BackupCodeCredentialVerifierInterface::class)->verify($identityId, $backupCodes[0]);
        self::assertTrue($verified);
    }
}
