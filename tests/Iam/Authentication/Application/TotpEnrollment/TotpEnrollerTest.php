<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\TotpEnrollment;

use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\TotpEnrollment\TotpEnrollerInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

final class TotpEnrollerTest extends AbstractIntegrationTestCase
{
    private TotpEnrollerInterface $enroller;
    private TotpCredentialFinderInterface $finder;
    private BackupCodeCredentialVerifierInterface $backupCodeVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enroller = $this->service(TotpEnrollerInterface::class);
        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->backupCodeVerifier = $this->service(BackupCodeCredentialVerifierInterface::class);
    }

    #[Test]
    public function itEnrolls(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $secret = faker()->totpSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();

        // When
        $backupCodes = $this->enroller->enrollFor($identityId, $secret, $code);

        // Then
        $result = $this->finder->activeOfIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame($identityId, $result->identityId);
        self::assertFalse($result->unenrolled);

        $backupCodeCount = self::getContainer()->getParameter('iam.authentication.backup_code_count');
        self::assertIsInt($backupCodeCount);
        self::assertNotNull($backupCodes);
        self::assertCount($backupCodeCount, $backupCodes);

        self::assertTrue($this->backupCodeVerifier->verify($identityId, $backupCodes[0]));
    }

    #[Test]
    public function itEnrollsWithoutNewBackupCodesWhenAlreadyEnrolled(): void
    {
        // Given
        $backupCodeCredential = BackupCodeCredentialFactory::new()->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class))->create();
        $this->store($backupCodeCredential);

        $secret = faker()->totpSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();

        // When
        $backupCodes = $this->enroller->enrollFor($backupCodeCredential->identityId, $secret, $code);

        // Then
        self::assertNull($backupCodes);
    }

    #[Test]
    public function itFailsWhenCodeIsInvalid(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $secret = faker()->totpSecret();

        // Then
        $this->expectException(InvalidTotpCodeException::class);

        // When
        $this->enroller->enrollFor($identityId, $secret, '000000');
    }
}
