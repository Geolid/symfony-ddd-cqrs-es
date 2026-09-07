<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\RequestErasure;

use Compliance\Erasure\Application\Command\PlaceHold\PlaceHold;
use Compliance\Erasure\Application\Command\RequestErasure\RequestErasure;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Application\SubjectStatus;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestErasureHandlerTest extends AbstractIntegrationTestCase
{
    private SubjectFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(SubjectFinderInterface::class);
    }

    #[Test]
    public function itRequestsWhenNew(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RequestErasure($subjectId));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(SubjectStatus::ERASING, $result->status);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();
        $this->dispatch(new PlaceHold($subjectId, 'compliance.tests.source', Uuid::uuid7()->toString()));

        // When
        $this->dispatch(new RequestErasure($subjectId));

        // Then
        $result = $this->finder->ofId($subjectId);
        self::assertSame(SubjectStatus::ERASING, $result->status);
    }
}
