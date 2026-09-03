<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\EventStore;

use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelIdentityRepositoryTest extends AbstractIntegrationTestCase
{
    private IdentityRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(IdentityRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();

        // When
        $this->repository->save($identity);
        $loaded = $this->repository->load($identity->id);

        // Then
        self::assertSame($identity->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->repository->load(IdentityBuilder::sample('id'));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->repository->save($identity);

        // When
        $exists = $this->repository->has($identity->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(IdentityBuilder::sample('id'));

        // Then
        self::assertFalse($notExists);
    }
}
