<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Patchlevel\Hydrator\Hydrator;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Finder\PaginatorInterface;
use Shared\Tests\Support\Double\DummyHydratable;
use Shared\Tests\Support\Double\FakeDbalFinder;
use Shared\Tests\Support\PaginationTrait;
use Support\TestCase\AbstractIntegrationTestCase;

final class AbstractPaginatableDbalFinderTest extends AbstractIntegrationTestCase
{
    /** @use PaginationTrait<PaginatorInterface<DummyHydratable>> */
    use PaginationTrait;

    private Connection $connection;
    private Hydrator $hydrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->service(Connection::class);
        $this->hydrator = $this->serviceAs('shared.hydration.hydrator', Hydrator::class);
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, ['epsilon', 'alpha', 'delta', 'beta', 'gamma']);

        // When
        $this->traversePages(
            expectedIds: ['alpha', 'beta', 'delta', 'epsilon', 'gamma'],
            pageSize: 2,
            askPage: static fn (int $page, int $itemsPerPage): PaginatorInterface => $finder->paginate($page, $itemsPerPage),
            idsOf: self::idsOf(...),
            metadataOf: PaginationMetadata::fromPaginator(...),
        );
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // Given
        $finder = new FakeDbalFinder($this->connection, $this->hydrator, []);

        // When
        $this->traverseEmptyPage(
            askPage: static fn (int $page, int $itemsPerPage): PaginatorInterface => $finder->paginate($page, $itemsPerPage),
            idsOf: self::idsOf(...),
            metadataOf: PaginationMetadata::fromPaginator(...),
            itemsPerPage: 20,
        );
    }

    /**
     * @param PaginatorInterface<DummyHydratable> $paginator
     *
     * @return list<string>
     */
    private static function idsOf(PaginatorInterface $paginator): array
    {
        return array_map(static fn (DummyHydratable $result): string => (string) $result->string, [...$paginator]);
    }
}
