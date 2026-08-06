<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
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
    public function itLoadsAnApiTokenCredentialItSaved(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()->withIdentifier('key-1')->create();

        // When
        $this->repository->save($credential);

        // Then
        $id = $credential->id();
        self::assertTrue($this->repository->has($id));
        self::assertFalse($this->repository->load($id)->isRevoked());
    }

    #[Test]
    public function itThrowsOnAnApiTokenCredentialItNeverSaved(): void
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
