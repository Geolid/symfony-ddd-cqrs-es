<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Finder;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Tests\Authentication\Support\Doubles\StubApiKeyHasher;
use Iam\Tests\Authentication\Support\Factory\ApiKeyCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $identityId = Uuid::uuid7()->toString();
        $keyId = KeyId::PREFIX.'0123456789abcdef';
        $hasher = new StubApiKeyHasher();
        $other = ApiKeyCredentialTestFactory::new()->withKeyId(KeyId::PREFIX.'fedcba9876543210')->withHasher($hasher)->create();
        $credential = ApiKeyCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withLabel('CI pipeline')
            ->withKeyId($keyId)
            ->withSecret('plain-secret')
            ->withIssuedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->withHasher($hasher)
            ->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofKeyId($keyId);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame('CI pipeline', $result->label);
        self::assertSame($keyId, $result->keyId);
        self::assertSame($hasher->hash('plain-secret'), $result->secretHash);
        self::assertFalse($result->revoked);
        self::assertSame('2026-01-01T00:00:00+00:00', $result->issuedAt->format('c'));
        self::assertNull($result->revokedAt);
        self::assertTrue($result->identityAuthenticatable);
    }

    #[Test]
    public function itThrowsWhenKeyIdNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialResultNotFoundException::class);

        // When
        $this->finder->ofKeyId(KeyId::PREFIX.'fedcba9876543210');
    }
}
