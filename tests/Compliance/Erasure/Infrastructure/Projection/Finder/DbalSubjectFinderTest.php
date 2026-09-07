<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Infrastructure\Projection\Finder;

use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Domain\Subject;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<\Compliance\Erasure\Application\Finder\Subject\SubjectResult>
 */
final class DbalSubjectFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByErasingBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = SubjectBuilder::new()->withRequestedAt($now->modify('-1 day'))->create();
        $due = SubjectBuilder::new()->withRequestedAt($now->modify('-31 days'))->create();
        $released = SubjectBuilder::new()->withRequestedAt($now->modify('-31 days'))->released()->create();
        $this->store($fresh, $due, $released);

        // When
        $results = iterator_to_array($this->finder()->erasingBefore($now->modify('-30 days')));

        // Then
        self::assertCount(1, $results);
        self::assertSame($due->id->toString(), $results[0]->id);
    }

    protected function finder(): SubjectFinderInterface
    {
        return $this->service(SubjectFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $subjects = SubjectBuilder::new()->many($count)->create();
        $this->store(...$subjects);

        return array_map(static fn (Subject $subject): string => $subject->id->toString(), $subjects);
    }

    protected function idOf(object $result): string
    {
        return $result->id;
    }
}
