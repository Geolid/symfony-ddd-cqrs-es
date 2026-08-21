<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Application\Command\RevokeApiKey\RevokeApiKey;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RevokeApiKeyHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRevokes(): void
    {
        // Given
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $credential = ApiKeyCredentialTestFactory::new()->withKeyId($keyId)->withHasher(new StubApiKeyHasher())->store();

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString()));

        // Then
        $result = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($keyId);
        self::assertTrue($result->revoked);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $credential = ApiKeyCredentialTestFactory::new()->withHasher(new StubApiKeyHasher())->revoked()->store();

        // When
        $this->dispatch(new RevokeApiKey($credential->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ApiKeyCredentialId::generate();

        // Then
        $this->expectException(ApiKeyCredentialNotFoundException::class);

        // When
        $this->dispatch(new RevokeApiKey($id->toString()));
    }
}
