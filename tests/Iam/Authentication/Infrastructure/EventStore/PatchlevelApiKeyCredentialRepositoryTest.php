<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Tests\Authentication\Support\Doubles\FakeApiKeyHasher;
use Iam\Tests\Authentication\Support\Builder\ApiKeyCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PatchlevelApiKeyCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private ApiKeyCredentialRepositoryInterface $repository;
    private FakeApiKeyHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ApiKeyCredentialRepositoryInterface::class);
        $this->hasher = new FakeApiKeyHasher();
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $builder = ApiKeyCredentialBuilder::new()->withHasher($this->hasher);
        $credential = $builder->create();

        // When
        $this->repository->save($credential);
        $loaded = $this->repository->load($credential->id);

        // Then
        self::assertSame($builder['id']->toString(), $loaded->id->toString());
        self::assertSame($builder['label']->value, $loaded->label->value);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ApiKeyCredentialNotFoundException::class);

        // When
        $this->repository->load(ApiKeyCredentialBuilder::sample('id'));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $credential = ApiKeyCredentialBuilder::new()->withHasher($this->hasher)->create();
        $this->repository->save($credential);

        // When
        $exists = $this->repository->has($credential->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ApiKeyCredentialBuilder::sample('id'));

        // Then
        self::assertFalse($notExists);
    }
}
