<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
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
    public function itLoadsAnIdentityItSaved(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();

        // When
        $this->repository->save($identity);

        // Then
        $id = $identity->id();
        self::assertTrue($this->repository->has($id));
        self::assertTrue($this->repository->load($id)->status()->isActive());
    }

    #[Test]
    public function itThrowsOnAnIdentityItNeverSaved(): void
    {
        // Given
        $id = IdentityId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
