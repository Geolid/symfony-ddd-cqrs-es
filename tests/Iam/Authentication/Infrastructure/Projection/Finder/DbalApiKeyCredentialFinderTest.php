<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakeApiKeyHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $other = ApiKeyCredentialBuilder::new()->withHasher($hasher)->create();

        $builder = ApiKeyCredentialBuilder::new()->withHasher($hasher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofKeyId($builder['keyId']->value);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame($builder['label']->value, $result->label);
        self::assertSame($builder['keyId']->value, $result->keyId);
        self::assertFalse($result->revoked);
        self::assertSame(
            $builder['issuedAt']->format(\DateTimeInterface::ATOM),
            $result->issuedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->revokedAt);
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($hasher->hash($builder['secret']), $result->secretHash);
    }

    #[Test]
    public function itThrowsWhenKeyIdNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->finder->ofKeyId(ApiKeyCredentialBuilder::sample('keyId')->value);
    }
}
