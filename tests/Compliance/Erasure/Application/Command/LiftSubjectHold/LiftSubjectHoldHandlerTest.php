<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\LiftSubjectHold;

use Compliance\Erasure\Application\Command\LiftSubjectHold\LiftSubjectHold;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class LiftSubjectHoldHandlerTest extends AbstractIntegrationTestCase
{
    private SubjectFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(SubjectFinderInterface::class);
    }

    #[Test]
    public function itLifts(): void
    {
        // Given
        $builder = SubjectBuilder::new()->heldBy();
        $subject = $builder->create();
        $this->store($subject);

        // When
        $this->dispatch(new LiftSubjectHold($subject->id->toString(), $builder['reference']->sourceType, $builder['reference']->sourceId));

        // Then
        $result = $this->finder->ofId($subject->id->toString());
        self::assertSame(0, $result->activeHoldCount);
    }

    #[Test]
    public function itIgnoresWhenNotActive(): void
    {
        // Given
        $subject = SubjectBuilder::new()->heldBy()->create();
        $this->store($subject);

        // When
        $this->dispatch(new LiftSubjectHold($subject->id->toString(), 'compliance.tests.source', Uuid::uuid7()->toString()));

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
        $this->dispatch(new LiftSubjectHold($subjectId, 'compliance.tests.source', Uuid::uuid7()->toString()));
    }
}
