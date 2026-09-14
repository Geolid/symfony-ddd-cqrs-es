<?php

declare(strict_types=1);

namespace Shared\Tests\Support\TestCase;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\IterableFinderInterface;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @template TResult of object
 */
abstract class AbstractIterableFinderTestCase extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $finder = $this->finder();
        $indexes = $this->seed(5);

        // When
        $results = iterator_to_array($finder);

        // Then
        self::assertSame($indexes, $this->resultIndexes($results));
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // Given
        $finder = $this->finder();

        // When
        $results = iterator_to_array($finder);

        // Then
        self::assertEmpty($results);
    }

    #[Test]
    public function itCounts(): void
    {
        // Given
        $finder = $this->finder();
        $indexes = $this->seed(3);

        // When
        $count = \count($finder);

        // Then
        self::assertSame(\count($indexes), $count);
    }

    #[Test]
    public function itCachesCount(): void
    {
        // Given
        $finder = $this->finder();
        $this->seed(3);

        // When
        $firstCount = \count($finder);
        $this->seed(1);
        $secondCount = \count($finder);

        // Then
        self::assertSame($firstCount, $secondCount);
    }

    #[Test]
    public function itIndexes(): void
    {
        // Given
        $finder = $this->finder();
        $indexes = $this->seed(3);

        // When
        $indexed = $finder->indexBy($this->indexOf(...));

        // Then
        self::assertSame($indexes, array_keys(iterator_to_array($indexed)));
    }

    /**
     * @return IterableFinderInterface<TResult>
     */
    abstract protected function finder(): IterableFinderInterface;

    /**
     * Seeds $count rows, returning their ids in the Finder's own default order.
     *
     * @return list<string>
     */
    abstract protected function seed(int $count): array;

    /**
     * @param TResult $result
     */
    abstract protected function indexOf(object $result): string;

    /**
     * @param iterable<TResult> $results
     *
     * @return list<string>
     */
    protected function resultIndexes(iterable $results): array
    {
        $indexes = [];
        foreach ($results as $result) {
            $indexes[] = $this->indexOf($result);
        }

        return $indexes;
    }
}
