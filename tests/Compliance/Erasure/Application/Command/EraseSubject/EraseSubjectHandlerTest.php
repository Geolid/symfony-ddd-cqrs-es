<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Command\EraseSubject;

use Compliance\Erasure\Application\Command\EraseSubject\EraseSubject;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Application\SubjectStatus;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseSubjectHandlerTest extends AbstractIntegrationTestCase
{
    private SubjectFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(SubjectFinderInterface::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $subject = SubjectBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-31 days'))->create();
        $this->store($subject);

        // When
        $this->dispatch(new EraseSubject($subject->id->toString()));

        // Then
        $result = $this->finder->ofId($subject->id->toString());
        self::assertSame(SubjectStatus::ERASED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenRetentionNotExpired(): void
    {
        // Given
        $subject = SubjectBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-1 day'))->create();
        $this->store($subject);

        // When
        $this->dispatch(new EraseSubject($subject->id->toString()));

        // Then
        $result = $this->finder->ofId($subject->id->toString());
        self::assertSame(SubjectStatus::ERASING, $result->status);
    }

    #[Test]
    public function itIgnoresWhenHoldsActive(): void
    {
        // Given
        $subject = SubjectBuilder::new()
            ->withRequestedAt(Clock::get()->now()->modify('-31 days'))
            ->heldBy()
            ->create();
        $this->store($subject);

        // When
        $this->dispatch(new EraseSubject($subject->id->toString()));

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
        $this->dispatch(new EraseSubject($subjectId));
    }
}
