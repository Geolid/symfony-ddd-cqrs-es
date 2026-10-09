<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetApiKeyCredentialByKeyId;

use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId\GetApiKeyCredentialByKeyId;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\KeyIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetApiKeyCredentialByKeyIdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $hasher = $this->service(ApiKeyHasherInterface::class);
        $secret = bin2hex(random_bytes(32));
        $credential = ApiKeyCredentialFactory::new()->withSecret($secret)->withHasher($hasher)->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetApiKeyCredentialByKeyId($credential->keyId->value));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credential->identityId, $result->identityId);
        self::assertSame($credential->label->value, $result->label);
        self::assertSame($credential->keyId->value, $result->keyId);
        self::assertFalse($result->revoked);

        self::assertSame($hasher->hash($secret), $result->secretHash);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetApiKeyCredentialByKeyId(KeyIdFactory::new()->create()->value));
    }
}
