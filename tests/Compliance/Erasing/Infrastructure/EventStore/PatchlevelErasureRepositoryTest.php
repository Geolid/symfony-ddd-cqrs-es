<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Infrastructure\EventStore;

use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelErasureRepositoryTest extends AbstractIntegrationTestCase
{
    private ErasureRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ErasureRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $erasure = ErasureBuilder::new()->create();

        // When
        $this->repository->save($erasure);
        $loaded = $this->repository->load($erasure->id);

        // Then
        self::assertSame($erasure->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->repository->load(ErasureId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $erasure = ErasureBuilder::new()->create();
        $this->repository->save($erasure);

        // When
        $exists = $this->repository->has($erasure->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ErasureId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
