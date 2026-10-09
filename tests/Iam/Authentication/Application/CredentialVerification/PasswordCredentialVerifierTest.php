<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\PasswordCredentialVerifier;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $password = PasswordFactory::new()->create()->value;
        $credential = PasswordCredentialFactory::new()->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($credential->identityId, $password);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $credential = PasswordCredentialFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($credential->identityId, 'WrongPassword456!');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itRefusesWhenCredentialMissing(): void
    {
        // When
        $verified = $this->verifier->verify(
            Uuid::uuid7()->toString(),
            PasswordFactory::new()->create()->value,
        );

        // Then
        self::assertFalse($verified);
    }
}
