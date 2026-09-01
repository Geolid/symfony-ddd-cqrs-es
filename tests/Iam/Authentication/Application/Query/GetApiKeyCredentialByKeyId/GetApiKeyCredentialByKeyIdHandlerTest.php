<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetApiKeyCredentialByKeyId;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId\GetApiKeyCredentialByKeyId;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetApiKeyCredentialByKeyIdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $hasher = $this->service(ApiKeyHasherInterface::class);
        $factory = ApiKeyCredentialTestFactory::new()->withHasher($hasher);
        $credential = $factory->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetApiKeyCredentialByKeyId($factory['keyId']->value));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($factory['identityId'], $result->identityId);
        self::assertSame($factory['label']->value, $result->label);
        self::assertSame($factory['keyId']->value, $result->keyId);
        self::assertFalse($result->revoked);
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($hasher->hash($factory['secret']), $result->secretHash);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetApiKeyCredentialByKeyId(ApiKeyCredentialTestFactory::sample('keyId')->value));
    }
}
