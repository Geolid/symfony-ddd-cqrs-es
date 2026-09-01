<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Password;

use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Password\PasswordCredentialVerifier;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private PasswordHasherInterface $hasher;
    private PasswordStrengthInterface $passwordStrength;
    private PasswordCredentialVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->passwordStrength = $this->service(PasswordStrengthInterface::class);
        $this->verifier = new PasswordCredentialVerifier($this->service(PasswordCredentialFinderInterface::class), $this->hasher);
    }

    #[Test]
    public function itAccepts(): void
    {
        // Given
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($factory['identityId'], $factory['password']->value);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($factory['identityId'], 'WrongPassword456!');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->verifier->verify(
            PasswordCredentialTestFactory::sample('identityId'),
            PasswordCredentialTestFactory::sample('password')->value,
        );
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();

        $factory = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();

        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->verifier->verify($identity->id->toString(), $factory['password']->value);
    }
}
