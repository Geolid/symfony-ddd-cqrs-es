<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RequestPasswordReset;

use Iam\Authentication\Application\Command\RequestPasswordReset\RequestPasswordReset;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestPasswordResetHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordStrengthSpecificationInterface $passwordStrength;

    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
        $this->hasher = $this->service(PasswordHasherInterface::class);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($identity, $credential);

        // When
        $this->dispatch(new RequestPasswordReset($identity->id->toString()));

        // Then
        $this->expectException(PasswordResetRequestedTooRecentlyException::class);

        $this->dispatch(new RequestPasswordReset($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenIdentityNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->dispatch(new RequestPasswordReset(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $credential = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($identity, $credential);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->dispatch(new RequestPasswordReset($identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenCredentialNotFound(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $this->store($identity);

        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->dispatch(new RequestPasswordReset($identity->id->toString()));
    }
}
