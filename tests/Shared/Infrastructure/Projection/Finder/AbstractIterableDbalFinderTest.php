<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Patchlevel\Hydrator\Hydrator;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\SortDirection;
use Shared\Tests\Support\Double\DummyHydratable;
use Shared\Tests\Support\Double\FakeDbalFinder;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

final class AbstractIterableDbalFinderTest extends AbstractIntegrationTestCase
{
    private Connection $connection;
    private Hydrator $hydrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->service(Connection::class);
        $this->hydrator = $this->serviceAs('shared.hydration.hydrator', Hydrator::class);
    }

    #[Test]
    public function itLists(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['gamma', 'alpha', 'beta']);

        // When
        $results = [...$finder];

        // Then
        self::assertSame(['alpha', 'beta', 'gamma'], $this->valuesOf($results));
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, []);

        // When
        $results = [...$finder];

        // Then
        self::assertSame([], $results);
    }

    #[Test]
    public function itCounts(): void
    {
        // Given
        $values = [faker()->unique()->word(), faker()->unique()->word(), faker()->unique()->word()];
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, $values);

        // When
        $count = \count($finder);

        // Then
        self::assertSame(\count($values), $count);
    }

    #[Test]
    public function itCountsWhenFiltered(): void
    {
        // Given
        $value = faker()->unique()->word();
        $values = [$value, faker()->unique()->word()];
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, $values);
        $totalCount = \count($finder);

        // When
        $filteredCount = \count($finder->byValue($value));

        // Then
        self::assertSame(\count($values), $totalCount);
        self::assertSame(1, $filteredCount);
    }

    #[Test]
    public function itCachesCount(): void
    {
        // Given
        $values = [faker()->unique()->word(), faker()->unique()->word()];
        $addedValue = faker()->unique()->word();
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, $values);

        // When
        $firstCount = \count($finder);
        $finder->values = [...$values, $addedValue];
        $secondCount = \count($finder);

        // Then
        self::assertSame(\count($values), $firstCount);
        self::assertSame(\count($values), $secondCount);
    }

    #[Test]
    public function itIndexes(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['gamma', 'alpha', 'beta']);

        // When
        $indexed = $finder->indexBy(static fn (DummyHydratable $result): string => (string) $result->string);

        // Then
        self::assertSame(['alpha', 'beta', 'gamma'], array_keys(iterator_to_array($indexed)));
    }

    #[Test]
    public function itSorts(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['gamma', 'alpha', 'beta']);

        // When
        $results = [...$finder->sortedBy(SortDirection::Descending)];

        // Then
        self::assertSame(['gamma', 'beta', 'alpha'], $this->valuesOf($results));
    }

    /**
     * @param list<DummyHydratable> $results
     *
     * @return list<string|null>
     */
    private function valuesOf(array $results): array
    {
        return array_map(static fn (DummyHydratable $result): ?string => $result->string, $results);
    }
}
