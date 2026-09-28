<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\ApiKeyCredentialVerifier;
use Iam\Authentication\Application\CredentialVerification\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
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
        $this->verifier = new ApiKeyCredentialVerifier(
            $this->service(ApiKeyCredentialFinderInterface::class),
            $this->hasher,
        );
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
}
