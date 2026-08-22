<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\PasswordCredentialVerifier;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private PasswordHasherInterface $hasher;
    private PasswordPolicyInterface $policy;
    private PasswordCredentialVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->policy = $this->service(PasswordPolicyInterface::class);
        $this->verifier = new PasswordCredentialVerifier($this->service(PasswordCredentialFinderInterface::class), $this->hasher);
    }

    #[Test]
    public function itVerifies(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();

        // Then
        self::assertTrue($this->verifier->verify($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));
        self::assertFalse($this->verifier->verify($identity->id->toString(), 'WrongPassword456!'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->verifier->verify(Uuid::uuid7()->toString(), 'Xk9$mQ2vLp7&zR4w');
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->verifier->verify($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w');
    }
}
