<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifier;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
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
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withSecret($secret);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], $code);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withSecret($secret);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenNotEnrolled(): void
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
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->revoked();
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], '000000');

        // Then
        self::assertFalse($verified);
    }
}
