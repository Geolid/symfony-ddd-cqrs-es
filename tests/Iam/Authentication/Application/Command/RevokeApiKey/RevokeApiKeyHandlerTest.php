<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\RevokeApiKey\RevokeApiKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RevokeApiKeyHandlerTest extends AbstractIntegrationTestCase
{
    private ApiKeyHasherInterface $hasher;
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(ApiKeyHasherInterface::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();

        $labelKey = UniqueKey::for(AuthenticationUniqueKey::API_KEY_CREDENTIAL_LABEL, $credential->identityId);
        $this->uniqueness->claim($labelKey, $credential->label->value, $credential->id->toString());

        $otherCredential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->withIdentityId($credential->identityId)->create();
        $this->uniqueness->claim($labelKey, $otherCredential->label->value, $otherCredential->id->toString());

        $this->store($credential, $otherCredential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $credential->identityId));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($credential->keyId->value);
        self::assertTrue($result->revoked);

        self::assertFalse($this->uniqueness->isClaimed($labelKey, $credential->label->value));
        self::assertTrue($this->uniqueness->isClaimed($labelKey, $otherCredential->label->value));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->revoked()->create();
        $this->store($credential);

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString(), $credential->identityId));

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
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = ApiKeyCredentialFactory::new()->withHasher($this->hasher)->create();
        $this->store($credential);

        // Then
        $this->expectException(ApiKeyCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RevokeApiKey(
            $credential->id->toString(),
            Uuid::uuid7()->toString(),
        ));
    }
}
