<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential\ApiKey;

use Iam\Authentication\Application\Credential\ApiKey\ApiKeyCredentialVerifier;
use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiKeyCredentialVerifierTest extends AbstractIntegrationTestCase
{
    private ApiKeyHasherInterface $hasher;
    private ApiKeyCredentialVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(ApiKeyHasherInterface::class);
        $this->verifier = new ApiKeyCredentialVerifier($this->service(ApiKeyCredentialFinderInterface::class), $this->hasher);
    }

    #[Test]
    public function itVerifies(): void
    {
        // Given
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $credential = ApiKeyCredentialTestFactory::new()
            ->withKeyId($keyId)
            ->withSecret('plain-secret')
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential);

        // Then
        self::assertTrue($this->verifier->verify($keyId, 'plain-secret'));
        self::assertFalse($this->verifier->verify($keyId, 'wrong-secret'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->verifier->verify(KeyId::PREFIX.'fedcba9876543210', 'plain-secret');
    }

    #[Test]
    public function itFailsWhenRevoked(): void
    {
        // Given
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $credential = ApiKeyCredentialTestFactory::new()
            ->withKeyId($keyId)
            ->withSecret('plain-secret')
            ->withHasher($this->hasher)
            ->revoked()
            ->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiKeyCredentialRevokedException::class);

        // When
        $this->verifier->verify($keyId, 'plain-secret');
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withKeyId($keyId)
            ->withSecret('plain-secret')
            ->withHasher($this->hasher)
            ->create();
        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->verifier->verify($keyId, 'plain-secret');
    }
}
