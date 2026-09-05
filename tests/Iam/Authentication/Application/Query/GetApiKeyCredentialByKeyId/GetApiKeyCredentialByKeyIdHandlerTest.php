<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetApiKeyCredentialByKeyId;

use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId\GetApiKeyCredentialByKeyId;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetApiKeyCredentialByKeyIdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $hasher = $this->service(ApiKeyHasherInterface::class);
        $builder = ApiKeyCredentialBuilder::new()->withHasher($hasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetApiKeyCredentialByKeyId($builder['keyId']->value));

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame($builder['label']->value, $result->label);
        self::assertSame($builder['keyId']->value, $result->keyId);
        self::assertFalse($result->revoked);
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($hasher->hash($builder['secret']), $result->secretHash);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetApiKeyCredentialByKeyId(ApiKeyCredentialBuilder::sample('keyId')->value));
    }
}
