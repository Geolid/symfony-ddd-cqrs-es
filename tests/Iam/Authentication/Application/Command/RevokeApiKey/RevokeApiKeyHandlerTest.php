<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Application\Command\RevokeApiKey\RevokeApiKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class RevokeApiKeyHandlerTest extends AbstractIntegrationTestCase
{
    private ApiKeyHasherInterface $hasher;
    private UniqueValueRegistryInterface $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(ApiKeyHasherInterface::class);
        $this->registry = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        // Given
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher);
        $credential = $builder->create();

        $labelKey = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $builder['identityId']);
        $this->registry->reserve($labelKey, $builder['label']->value, $credential->id->toString());

        $this->store($credential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $builder['identityId']));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($builder['keyId']->value);
        self::assertTrue($result->revoked);
        self::assertFalse($this->registry->exists($labelKey, $builder['label']->value));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->revoked();
        $credential = $builder->create();
        $this->store($credential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $builder['identityId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialNotFoundException::class);

        // When
        $this->dispatch(new RevokeApiKey(
            ApiKeyCredentialBuilder::sample('id')->toString(),
            ApiKeyCredentialBuilder::sample('identityId'),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiKeyCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RevokeApiKey(
            $credential->id->toString(),
            ApiKeyCredentialBuilder::sample('identityId'),
        ));
    }
}
