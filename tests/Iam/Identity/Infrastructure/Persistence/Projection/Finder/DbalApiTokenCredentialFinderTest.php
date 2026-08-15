<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Finder;

use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Exception\ResultNotFoundException;
use Support\AbstractIntegrationTestCase;

final class DbalApiTokenCredentialFinderTest extends AbstractIntegrationTestCase
{
    private ApiTokenCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ApiTokenCredentialFinderInterface::class);
    }

    #[Test]
    public function itGetsByIdentifier(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier('key_operator')
            ->withLabel('Operator key')
            ->withHasher(new DummySecretHasher())
            ->store();

        // When
        $result = $this->finder->ofIdentifier('key_operator');

        // Then
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame('key_operator', $result->identifier);
        self::assertSame('Operator key', $result->label);
        self::assertFalse($result->revoked);
    }

    #[Test]
    public function itThrowsOnAnUnknownIdentifier(): void
    {
        // Then
        $this->expectException(ResultNotFoundException::class);

        // When
        $this->finder->ofIdentifier('key_unknown');
    }

    #[Test]
    public function itFiltersByIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withHasher(new DummySecretHasher())
            ->store();
        ApiTokenCredentialTestFactory::new()
            ->withIdentityId(Uuid::uuid7()->toString())
            ->withHasher(new DummySecretHasher())
            ->store();

        // When
        $results = iterator_to_array($this->finder->byIdentity($identityId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($credential->id()->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersActive(): void
    {
        // Given
        $active = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->store();
        ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->revoked()->store();

        // When
        $results = iterator_to_array($this->finder->active());

        // Then
        self::assertCount(1, $results);
        self::assertSame($active->id()->toString(), $results[0]->id);
    }
}
