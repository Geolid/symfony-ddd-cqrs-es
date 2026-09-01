<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\ApiKey;

use Iam\Authentication\Application\ApiKey\ApiKeyCredentialVerifier;
use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
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
    public function itAccepts(): void
    {
        // Given
        $factory = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($factory['keyId']->value, $factory['secret']);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $factory = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($factory['keyId']->value, 'wrong-secret');

        // Then
        self::assertFalse($verified);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->verifier->verify(
            ApiKeyCredentialTestFactory::sample('keyId')->value,
            ApiKeyCredentialTestFactory::sample('secret'),
        );
    }

    #[Test]
    public function itFailsWhenRevoked(): void
    {
        // Given
        $factory = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher)->revoked();
        $credential = $factory->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiKeyCredentialRevokedException::class);

        // When
        $this->verifier->verify($factory['keyId']->value, $factory['secret']);
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();

        $factory = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->verifier->verify($factory['keyId']->value, $factory['secret']);
    }
}
