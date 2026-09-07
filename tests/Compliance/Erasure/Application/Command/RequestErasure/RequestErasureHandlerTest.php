<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\RequestErasure;

use Compliance\Erasure\Application\Command\RequestErasure\RequestErasure;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Application\SubjectStatus;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
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
    public function itRequests(): void
    {
        // Given
        $subject = SubjectBuilder::new()->create();
        $this->store($subject);

        // When
        $this->dispatch(new RequestErasure($subject->id->toString()));

        // Then
        $result = $this->finder->ofId($subject->id->toString());
        self::assertSame(SubjectStatus::ERASING, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $subjectId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(SubjectNotFoundException::class);

        // When
        $this->dispatch(new RequestErasure($subjectId));
    }
}
