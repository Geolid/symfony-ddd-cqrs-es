<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Patchlevel\Hydrator\Hydrator;
use PHPUnit\Framework\Attributes\Test;
use Shared\Infrastructure\Projection\Finder\Exception\NonUniqueResultException;
use Shared\Tests\Support\Double\FakeDbalFinder;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['first', 'second']);

        // When
        $first = $finder->ofValue('first');
        $second = $finder->ofValue('second');

        // Then
        self::assertSame('first', $first?->string);
        self::assertSame('second', $second?->string);
    }

    #[Test]
    public function itFindsNothing(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['first']);

        // When
        $result = $finder->ofValue('unknown');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itThrowsWhenNotUnique(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['twin', 'twin']);

        // Then
        $this->expectException(NonUniqueResultException::class);

        // When
        $finder->ofValue('twin');
    }
}
