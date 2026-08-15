<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiTokenCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private ApiTokenCredentialRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ApiTokenCredentialRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsASavedApiTokenCredential(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()->withHasher(new DummySecretHasher())->create();

        // When
        $this->repository->save($credential);

        // Then
        $id = $credential->id();
        self::assertTrue($this->repository->has($id));
        $this->repository->load($id);
    }

    #[Test]
    public function itThrowsOnAnUnsavedApiTokenCredential(): void
    {
        // Given
        $id = ApiTokenCredentialId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(ApiTokenCredentialNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
