<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\LiftHold;

use Compliance\Erasure\Application\Command\LiftHold\LiftHold;
use Compliance\Erasure\Application\Command\PlaceHold\PlaceHold;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class LiftHoldHandlerTest extends AbstractIntegrationTestCase
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
        $subjectId = Uuid::uuid7()->toString();
        $sourceId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceHold($subjectId, 'compliance.tests.source', $sourceId));

        // When
        $this->dispatch(new LiftHold($subjectId, 'compliance.tests.source', $sourceId));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(0, $result->activeHoldCount);
    }

    #[Test]
    public function itIgnoresWhenNotActive(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceHold($subjectId, 'compliance.tests.source', Uuid::uuid7()->toString()));

        // When
        $this->dispatch(new LiftHold($subjectId, 'compliance.tests.source', Uuid::uuid7()->toString()));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(1, $result->activeHoldCount);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new LiftHold($subjectId, 'compliance.tests.source', Uuid::uuid7()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
