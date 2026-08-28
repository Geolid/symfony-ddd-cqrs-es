<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Application\Command\RevokeApiKey\RevokeApiKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class RevokeApiKeyHandlerTest extends AbstractIntegrationTestCase
{
    private ApiKeyHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(ApiKeyHasherInterface::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $credential = ApiKeyCredentialTestFactory::new()->withIdentityId($identity->id->toString())->withKeyId($keyId)->withLabel('CI pipeline')->withHasher($this->hasher)->create();
        $this->store($identity, $credential);
        $this->service(UniqueValueRegistryInterface::class)->reserve(
            UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identity->id->toString()),
            'CI pipeline',
            $credential->id->toString(),
        );

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $identity->id->toString()));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($keyId);
        self::assertTrue($result->revoked);
        self::assertFalse($this->service(UniqueValueRegistryInterface::class)->exists(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $identity->id->toString()), 'CI pipeline'));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $credential = ApiKeyCredentialTestFactory::new()->withIdentityId($identity->id->toString())->withHasher($this->hasher)->revoked()->create();
        $this->store($identity, $credential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ApiKeyCredentialId::generate();
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // Then
        $this->expectException(ApiKeyCredentialNotFoundException::class);

        // When
        $this->dispatch(new RevokeApiKey($id->toString(), $identity->id->toString()));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = ApiKeyCredentialTestFactory::new()->withHasher($this->hasher)->create();
        $identity = IdentityTestFactory::new()->create();
        $this->store($credential, $identity);

        // Then
        $this->expectException(ApiKeyCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $identity->id->toString()));
    }
}
