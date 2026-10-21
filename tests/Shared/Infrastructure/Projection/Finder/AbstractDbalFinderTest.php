<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Patchlevel\Hydrator\Hydrator;
use PHPUnit\Framework\Attributes\Test;
use Shared\Infrastructure\Projection\Finder\Exception\NonUniqueResultException;
use Shared\Tests\Support\Double\FakeDbalFinder;
use Support\TestCase\AbstractIntegrationTestCase;

use function Zenstruck\Foundry\faker;

final class AbstractDbalFinderTest extends AbstractIntegrationTestCase
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
    public function itFinds(): void
    {
        // Given
        $value = faker()->unique()->word();
        $otherValue = faker()->unique()->word();
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, [$otherValue, $value]);

        // When
        $result = $finder->ofValue($value);

        // Then
        self::assertSame($value, $result?->string);
    }

    #[Test]
    public function itFilters(): void
    {
        // Given
        $value = faker()->unique()->word();
        $otherValue = faker()->unique()->word();
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, [$value, $otherValue]);
        $finder->valued($value);

        // When
        $count = \count($finder);

        // Then
        self::assertSame(2, $count);
    }

    #[Test]
    public function itFindsNothing(): void
    {
        // Given
        $value = faker()->unique()->word();
        $unknownValue = faker()->unique()->word();
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, [$value]);

        // When
        $result = $finder->ofValue($unknownValue);

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itThrowsWhenNotUnique(): void
    {
        // Given
        $twin = faker()->unique()->word();
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, [$twin, $twin]);

        // Then
        $this->expectException(NonUniqueResultException::class);

        // When
        $finder->ofValue($twin);
    }
}
