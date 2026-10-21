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
        self::assertSame(['alpha', 'beta', 'gamma'], array_map(static fn (DummyHydratable $result): ?string => $result->string, $results));
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
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['gamma', 'alpha', 'beta']);

        // When
        $count = \count($finder);

        // Then
        self::assertSame(3, $count);
    }

    #[Test]
    public function itCachesCount(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['alpha', 'beta']);

        // When
        $firstCount = \count($finder);
        $finder->values = ['alpha', 'beta', 'gamma'];
        $secondCount = \count($finder);

        // Then
        self::assertSame(2, $firstCount);
        self::assertSame(2, $secondCount);
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
        self::assertSame(['gamma', 'beta', 'alpha'], array_map(static fn (DummyHydratable $result): ?string => $result->string, $results));
    }

    #[Test]
    public function itThrowsWhenDefaultSortEmpty(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['alpha'], []); // @phpstan-ignore argument.type

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        [...$finder];
    }
}
