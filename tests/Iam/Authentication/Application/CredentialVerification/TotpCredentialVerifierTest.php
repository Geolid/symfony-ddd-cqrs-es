<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifier;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeTotpVerifier;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class TotpCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private TotpCipherInterface $cipher;
    private TotpVerifierInterface $verifier;
    private TotpCredentialVerifier $credentialVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->verifier = $this->service(TotpVerifierInterface::class);
        $this->credentialVerifier = new TotpCredentialVerifier(
            $this->service(TotpCredentialFinderInterface::class),
            $this->service(IdentityFinderInterface::class),
            $this->cipher,
            $this->verifier,
        );
    }

    #[Test]
    public function itAccepts(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $builder = TotpCredentialBuilder::new()
            ->withCipher($this->cipher)
            ->withSecret($secret)
            ->withVerifier($this->verifier)
            ->confirmed($code);
        $credential = $builder->create();
        $identity = IdentityBuilder::new()->withId($builder['identityId'])->activated()->create();
        $this->store($credential, $identity);

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
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $builder = TotpCredentialBuilder::new()
            ->withCipher($this->cipher)
            ->withSecret($secret)
            ->withVerifier($this->verifier)
            ->confirmed($code);
        $credential = $builder->create();
        $identity = IdentityBuilder::new()->withId($builder['identityId'])->activated()->create();
        $this->store($credential, $identity);

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
    public function itRefusesWhenNotConfirmed(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->create();

        $credential = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->cipher)
            ->withVerifier(new FakeTotpVerifier())
            ->confirmed()
            ->create();
        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->credentialVerifier->verify($identity->id->toString(), '000000');
    }
}
