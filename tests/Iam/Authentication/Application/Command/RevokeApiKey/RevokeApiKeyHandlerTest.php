<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Application\Command\RevokeApiKey\RevokeApiKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
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
        $factory = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher);
        $credential = $factory->create();

        $labelKey = UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $factory['identityId']);
        $this->registry->reserve($labelKey, $factory['label']->value, $credential->id->toString());

        $this->store($credential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $factory['identityId']));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($factory['keyId']->value);
        self::assertTrue($result->revoked);
        self::assertFalse($this->registry->exists($labelKey, $factory['label']->value));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $factory = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher)->revoked();
        $credential = $factory->create();
        $this->store($credential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $factory['identityId']));

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
            ApiKeyCredentialTestFactory::sample('id')->toString(),
            ApiKeyCredentialTestFactory::sample('identityId'),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher)->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiKeyCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RevokeApiKey(
            $credential->id->toString(),
            ApiKeyCredentialTestFactory::sample('identityId'),
        ));
    }
}
