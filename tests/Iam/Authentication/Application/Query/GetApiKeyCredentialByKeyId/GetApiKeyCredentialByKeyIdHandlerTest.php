<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetApiKeyCredentialByKeyId;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId\GetApiKeyCredentialByKeyId;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetApiKeyCredentialByKeyIdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $hasher = $this->service(ApiKeyHasherInterface::class);
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withLabel('CI pipeline')
            ->withKeyId($keyId)
            ->withSecret('plain-secret')
            ->withHasher($hasher)
            ->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetApiKeyCredentialByKeyId($keyId));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame('CI pipeline', $result->label);
        self::assertSame($keyId, $result->keyId);
        self::assertSame($hasher->hash('plain-secret'), $result->secretHash);
        self::assertFalse($result->revoked);
        self::assertTrue($result->identityAuthenticatable);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetApiKeyCredentialByKeyId(KeyId::PREFIX.'fedcba9876543210'));
    }
}
