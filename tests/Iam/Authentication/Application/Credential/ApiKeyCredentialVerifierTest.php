<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Credential\ApiKeyCredentialVerifier;
use Iam\Authentication\Application\Credential\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['keyId']->value, $builder['secret']);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $verified = $this->verifier->verify($builder['keyId']->value, 'wrong-secret');

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
            ApiKeyCredentialBuilder::sample('keyId')->value,
            ApiKeyCredentialBuilder::sample('secret'),
        );
    }

    #[Test]
    public function itFailsWhenRevoked(): void
    {
        // Given
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->revoked();
        $credential = $builder->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiKeyCredentialRevokedException::class);

        // When
        $this->verifier->verify($builder['keyId']->value, $builder['secret']);
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->create();

        $builder = ApiKeyCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($credential, $identity);

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->verifier->verify($builder['keyId']->value, $builder['secret']);
    }
}
