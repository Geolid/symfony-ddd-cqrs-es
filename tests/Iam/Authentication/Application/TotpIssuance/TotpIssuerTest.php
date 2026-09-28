<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\TotpIssuance\TotpIssuerInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class TotpIssuerTest extends AbstractIntegrationTestCase
{
    private TotpIssuerInterface $issuer;
    private TotpCredentialFinderInterface $finder;
    private BackupCodeCredentialVerifierInterface $backupCodeVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->issuer = $this->service(TotpIssuerInterface::class);
        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->backupCodeVerifier = $this->service(BackupCodeCredentialVerifierInterface::class);
    }

    #[Test]
    public function itIssues(): void
    {
        // Given
        $identityId = TotpCredentialBuilder::sample('identityId');
        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();

        // When
        $backupCodes = $this->issuer->issueFor($identityId, $secret, $code);

        // Then
        $result = $this->finder->activeOfIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame($identityId, $result->identityId);
        self::assertFalse($result->revoked);

        $backupCodeCount = self::getContainer()->getParameter('iam.authentication.backup_code_count');
        self::assertIsInt($backupCodeCount);
        self::assertNotNull($backupCodes);
        self::assertCount($backupCodeCount, $backupCodes);

        self::assertTrue($this->backupCodeVerifier->verify($identityId, $backupCodes[0]));
    }

    #[Test]
    public function itIssuesWithoutNewBackupCodesWhenAlreadyIssued(): void
    {
        // Given
        $backupCodeBuilder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class));
        $backupCodeCredential = $backupCodeBuilder->create();
        $this->store($backupCodeCredential);

        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();

        // When
        $backupCodes = $this->issuer->issueFor($backupCodeBuilder['identityId'], $secret, $code);

        // Then
        self::assertNull($backupCodes);
    }

    #[Test]
    public function itFailsWhenCodeIsInvalid(): void
    {
        // Given
        $identityId = TotpCredentialBuilder::sample('identityId');
        $secret = TOTP::generate()->getSecret();

        // Then
        $this->expectException(InvalidTotpCodeException::class);

        // When
        $this->issuer->issueFor($identityId, $secret, '000000');
    }
}
