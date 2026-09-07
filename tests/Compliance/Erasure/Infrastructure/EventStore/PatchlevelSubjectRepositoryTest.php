<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Infrastructure\EventStore;

use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelSubjectRepositoryTest extends AbstractIntegrationTestCase
{
    private SubjectRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(SubjectRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $subject = SubjectBuilder::new()->create();

        // When
        $this->repository->save($subject);
        $loaded = $this->repository->load($subject->id);

        // Then
        self::assertSame($subject->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(SubjectNotFoundException::class);

        // When
        $this->repository->load(SubjectId::fromString(Uuid::uuid7()->toString()));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $subject = SubjectBuilder::new()->create();
        $this->repository->save($subject);

        // When
        $exists = $this->repository->has($subject->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(SubjectId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
