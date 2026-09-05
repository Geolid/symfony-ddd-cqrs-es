<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Credential\PasswordCredentialVerifier;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['identityId'], $builder['password']->value);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['identityId'], 'WrongPassword456!');

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
            PasswordCredentialBuilder::sample('identityId'),
            PasswordCredentialBuilder::sample('password')->value,
        );
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->create();

        $builder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();

        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->verifier->verify($identity->id->toString(), $builder['password']->value);
    }
}
