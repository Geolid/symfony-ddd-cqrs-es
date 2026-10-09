<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifier;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class TotpCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private TotpCipherInterface $cipher;
    private TotpCredentialVerifier $credentialVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->credentialVerifier = new TotpCredentialVerifier(
            $this->service(TotpCredentialFinderInterface::class),
            $this->cipher,
            $this->service(TotpVerifierInterface::class),
        );
    }

    #[Test]
    public function itAccepts(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->withSecret($secret)->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($credential->identityId, $code);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->withSecret($secret)->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($credential->identityId, '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenNotEnrolled(): void
    {
        // When
        $verified = $this->credentialVerifier->verify(Uuid::uuid7()->toString(), '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenUnenrolled(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->unenrolled()->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($credential->identityId, '000000');

        // Then
        self::assertFalse($verified);
    }
}
