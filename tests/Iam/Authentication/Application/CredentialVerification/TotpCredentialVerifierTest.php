<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifier;
use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
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
        $this->credentialVerifier = new TotpCredentialVerifier($this->service(TotpCredentialFinderInterface::class), $this->cipher, $this->verifier);
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
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $builder = TotpCredentialBuilder::new()
            ->withCipher($this->cipher)
            ->withSecret($secret)
            ->withVerifier($this->verifier)
            ->confirmed($code);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->credentialVerifier->verify($builder['identityId'], '000000');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialResultNotFoundException::class);

        // When
        $this->credentialVerifier->verify(TotpCredentialBuilder::sample('identityId'), '000000');
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->create();

        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();
        $builder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->cipher)
            ->withSecret($secret)
            ->withVerifier($this->verifier)
            ->confirmed($code);
        $credential = $builder->create();
        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->credentialVerifier->verify($identity->id->toString(), '000000');
    }
}
