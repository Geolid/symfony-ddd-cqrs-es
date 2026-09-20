<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity\GetPasswordCredentialByIdentity;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetPasswordCredentialByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordHasherInterface $hasher;
    private PasswordStrengthSpecificationInterface $passwordStrength;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
    }

    #[Test]
    public function itGets(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $credentialBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength);
        $credential = $credentialBuilder->create();
        $this->store($identity, $credential);

        // When
        $result = $this->ask(new GetPasswordCredentialByIdentity($identity->id->toString()));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame(
            $credentialBuilder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->passwordChangedAt->format(\DateTimeInterface::ATOM),
        );
    }

    #[Test]
    public function itFailsWhenNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength)
            ->create();
        $this->store($identity, $credential);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->ask(new GetPasswordCredentialByIdentity($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenPasswordCredentialNotFound(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $this->store($identity);

        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetPasswordCredentialByIdentity($identity->id->toString()));
    }
}
