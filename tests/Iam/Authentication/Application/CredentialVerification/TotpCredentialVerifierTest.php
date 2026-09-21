<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifier;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class TotpCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private TotpCipherInterface $cipher;
    private TotpBackupCodeHasherInterface $backupCodeHasher;
    private TotpCredentialVerifier $credentialVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->backupCodeHasher = $this->service(TotpBackupCodeHasherInterface::class);
        $this->credentialVerifier = new TotpCredentialVerifier(
            $this->service(TotpCredentialFinderInterface::class),
            $this->cipher,
            $this->service(TotpVerifierInterface::class),
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itAccepts(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->withSecret($secret);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], $code);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itAcceptsBackupCode(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->withSecret($secret);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenNotIssued(): void
    {
        // When
        $verified = $this->credentialVerifier->verify(TotpCredentialBuilder::sample('identityId'), '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenRevoked(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->revoked();
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesBackupCodeWhenAlreadyConsumed(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->backupCodeConsumed();
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], $builder['consumedBackupCode']);

        // Then
        self::assertFalse($verified);
    }
}
