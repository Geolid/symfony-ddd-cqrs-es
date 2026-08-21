<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\PasswordCredentialVerifier;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class PasswordCredentialVerifierTest extends TestCase
{
    #[Test]
    public function itVerifies(): void
    {
        // Given
        $hasher = new StubPasswordHasher();
        $identityId = Uuid::uuid7()->toString();
        $result = new PasswordCredentialResult(
            id: Uuid::uuid7()->toString(),
            identityId: $identityId,
            login: 'ada.lovelace',
            passwordHash: $hasher->hash('Xk9$mQ2vLp7&zR4w'),
            identityAuthenticatable: true,
        );
        $finder = $this->createStub(PasswordCredentialFinderInterface::class);
        $finder->method('ofIdentityId')->willReturn($result);
        $verifier = new PasswordCredentialVerifier($finder, $hasher);

        // Then
        self::assertTrue($verifier->verify($identityId, 'Xk9$mQ2vLp7&zR4w'));
        self::assertFalse($verifier->verify($identityId, 'WrongPassword456!'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $finder = $this->createStub(PasswordCredentialFinderInterface::class);
        $finder->method('ofIdentityId')->willThrowException(PasswordCredentialResultNotFoundException::forIdentity($identityId));
        $verifier = new PasswordCredentialVerifier($finder, new StubPasswordHasher());

        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $verifier->verify($identityId, 'Xk9$mQ2vLp7&zR4w');
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $hasher = new StubPasswordHasher();
        $identityId = Uuid::uuid7()->toString();
        $result = new PasswordCredentialResult(
            id: Uuid::uuid7()->toString(),
            identityId: $identityId,
            login: 'ada.lovelace',
            passwordHash: $hasher->hash('Xk9$mQ2vLp7&zR4w'),
            identityAuthenticatable: false,
        );
        $finder = $this->createStub(PasswordCredentialFinderInterface::class);
        $finder->method('ofIdentityId')->willReturn($result);
        $verifier = new PasswordCredentialVerifier($finder, $hasher);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $verifier->verify($identityId, 'Xk9$mQ2vLp7&zR4w');
    }
}
