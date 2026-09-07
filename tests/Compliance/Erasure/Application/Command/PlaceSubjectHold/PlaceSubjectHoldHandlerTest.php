<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\PlaceSubjectHold;

use Compliance\Erasure\Application\Command\PlaceSubjectHold\PlaceSubjectHold;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PlaceSubjectHoldHandlerTest extends AbstractIntegrationTestCase
{
    private SubjectFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(SubjectFinderInterface::class);
    }

    #[Test]
    public function itPlaces(): void
    {
        // Given
        $subject = SubjectBuilder::new()->create();
        $this->store($subject);

        // When
        $this->dispatch(new PlaceSubjectHold($subject->id->toString(), 'compliance.tests.source', Uuid::uuid7()->toString()));

        // Then
        $result = $this->finder->ofId($subject->id->toString());
        self::assertSame(1, $result->activeHoldCount);
    }

    #[Test]
    public function itIgnoresWhenAlreadyPlaced(): void
    {
        // Given
        $builder = SubjectBuilder::new()->heldBy();
        $subject = $builder->create();
        $this->store($subject);

        // When
        $this->dispatch(new PlaceSubjectHold($subject->id->toString(), $builder['reference']->sourceType, $builder['reference']->sourceId));

        // Then
        $result = $this->finder->ofId($subject->id->toString());
        self::assertSame(1, $result->activeHoldCount);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(SubjectNotFoundException::class);

        // When
        $this->dispatch(new PlaceSubjectHold($subjectId, 'compliance.tests.source', Uuid::uuid7()->toString()));
    }
}
