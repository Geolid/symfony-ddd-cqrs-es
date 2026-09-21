<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\PasswordCredentialVerifier;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PasswordCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private PasswordHasherInterface $hasher;
    private PasswordStrengthSpecificationInterface $passwordStrength;
    private PasswordCredentialVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
        $this->verifier = new PasswordCredentialVerifier(
            $this->service(PasswordCredentialFinderInterface::class),
            $this->hasher,
        );
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
    public function itRefusesWhenCredentialMissing(): void
    {
        // When
        $verified = $this->verifier->verify(
            PasswordCredentialBuilder::sample('identityId'),
            PasswordCredentialBuilder::sample('password')->value,
        );

        // Then
        self::assertFalse($verified);
    }
}
