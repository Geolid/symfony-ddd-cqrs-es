<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Doubles\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DbalApiKeyCredentialFinderTest extends AbstractIntegrationTestCase
{
    private ApiKeyCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ApiKeyCredentialFinderInterface::class);
    }

    #[Test]
    public function itGetsByKeyId(): void
    {
        // Given
        $hasher = new FakeApiKeyHasher();
        $other = ApiKeyCredentialTestFactory::new()->withHasher($hasher)->create();

        $factory = ApiKeyCredentialTestFactory::new()->withHasher($hasher);
        $credential = $factory->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofKeyId($factory['keyId']->value);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($factory['identityId'], $result->identityId);
        self::assertSame($factory['label']->value, $result->label);
        self::assertSame($factory['keyId']->value, $result->keyId);
        self::assertFalse($result->revoked);
        self::assertSame(
            $factory['issuedAt']->format(\DateTimeImmutable::ATOM),
            $result->issuedAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertNull($result->revokedAt);
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($hasher->hash($factory['secret']), $result->secretHash);
    }

    #[Test]
    public function itThrowsWhenKeyIdNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->finder->ofKeyId(ApiKeyCredentialTestFactory::sample('keyId')->value);
    }
}
