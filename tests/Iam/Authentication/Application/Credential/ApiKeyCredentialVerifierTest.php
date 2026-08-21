<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\ApiKeyCredentialVerifier;
use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class ApiKeyCredentialVerifierTest extends TestCase
{
    #[Test]
    public function itVerifies(): void
    {
        // Given
        $hasher = new StubApiKeyHasher();
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $result = new ApiKeyCredentialResult(
            id: Uuid::uuid7()->toString(),
            identityId: Uuid::uuid7()->toString(),
            label: 'CI pipeline',
            keyId: $keyId,
            secretHash: $hasher->hash('plain-secret'),
            revoked: false,
            identityAuthenticatable: true,
        );
        $finder = $this->createStub(ApiKeyCredentialFinderInterface::class);
        $finder->method('ofKeyId')->willReturn($result);
        $verifier = new ApiKeyCredentialVerifier($finder, $hasher);

        // Then
        self::assertTrue($verifier->verify($keyId, 'plain-secret'));
        self::assertFalse($verifier->verify($keyId, 'wrong-secret'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $finder = $this->createStub(ApiKeyCredentialFinderInterface::class);
        $finder->method('ofKeyId')->willThrowException(ApiKeyCredentialResultNotFoundException::forKeyId($keyId));
        $verifier = new ApiKeyCredentialVerifier($finder, new StubApiKeyHasher());

        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $verifier->verify($keyId, 'plain-secret');
    }

    #[Test]
    public function itFailsWhenRevoked(): void
    {
        // Given
        $hasher = new StubApiKeyHasher();
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $result = new ApiKeyCredentialResult(
            id: Uuid::uuid7()->toString(),
            identityId: Uuid::uuid7()->toString(),
            label: 'CI pipeline',
            keyId: $keyId,
            secretHash: $hasher->hash('plain-secret'),
            revoked: true,
            identityAuthenticatable: true,
        );
        $finder = $this->createStub(ApiKeyCredentialFinderInterface::class);
        $finder->method('ofKeyId')->willReturn($result);
        $verifier = new ApiKeyCredentialVerifier($finder, $hasher);

        // Then
        $this->expectException(ApiKeyCredentialRevokedException::class);

        // When
        $verifier->verify($keyId, 'plain-secret');
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $hasher = new StubApiKeyHasher();
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $result = new ApiKeyCredentialResult(
            id: Uuid::uuid7()->toString(),
            identityId: Uuid::uuid7()->toString(),
            label: 'CI pipeline',
            keyId: $keyId,
            secretHash: $hasher->hash('plain-secret'),
            revoked: false,
            identityAuthenticatable: false,
        );
        $finder = $this->createStub(ApiKeyCredentialFinderInterface::class);
        $finder->method('ofKeyId')->willReturn($result);
        $verifier = new ApiKeyCredentialVerifier($finder, $hasher);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $verifier->verify($keyId, 'plain-secret');
    }
}
