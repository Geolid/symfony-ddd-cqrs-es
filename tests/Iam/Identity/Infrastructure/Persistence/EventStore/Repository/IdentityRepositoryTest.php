<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class IdentityRepositoryTest extends AbstractIntegrationTestCase
{
    private IdentityRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(IdentityRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsASavedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();

        // When
        $this->repository->save($identity);

        // Then
        $id = $identity->id();
        self::assertTrue($this->repository->has($id));
        $this->repository->load($id);
    }

    #[Test]
    public function itThrowsOnAnUnsavedIdentity(): void
    {
        // Given
        $id = IdentityId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
