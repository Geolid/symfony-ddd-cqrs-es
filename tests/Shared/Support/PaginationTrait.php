<?php

declare(strict_types=1);

namespace Shared\Tests\Support;

use Shared\Application\Finder\PaginationMetadata;

/**
 * @template TPage of object
 */
trait PaginationTrait
{
    /**
     * Fetches every full page implied by $expectedIds chunked by $pageSize, plus one page past
     * the end, asserting item order and pagination metadata on each. Returns the in-bounds
     * pages, 1-indexed, for the caller's own domain assertions.
     *
     * @param list<string>                        $expectedIds
     * @param positive-int                        $pageSize
     * @param \Closure(int, int): TPage           $askPage
     * @param \Closure(TPage): list<string>       $idsOf
     * @param \Closure(TPage): PaginationMetadata $metadataOf
     *
     * @return array<int, TPage>
     */
    protected function traversePages(
        array $expectedIds,
        int $pageSize,
        \Closure $askPage,
        \Closure $idsOf,
        \Closure $metadataOf,
    ): array {
        $chunks = array_chunk($expectedIds, $pageSize);
        $lastPage = \count($chunks);
        $totalItems = \count($expectedIds);

        $pages = [];
        foreach (array_keys($chunks) as $index) {
            $currentPage = $index + 1;
            $pages[$currentPage] = $askPage($currentPage, $pageSize);
        }
        $outOfBoundsPage = $askPage($lastPage + 1, $pageSize);

        foreach ($chunks as $index => $expectedPageIds) {
            $currentPage = $index + 1;
            $metadata = $metadataOf($pages[$currentPage]);

            self::assertSame($expectedPageIds, $idsOf($pages[$currentPage]));
            self::assertSame($totalItems, $metadata->totalItems);
            self::assertSame($lastPage, $metadata->lastPage);
            self::assertSame($currentPage, $metadata->currentPage);
            self::assertSame($pageSize, $metadata->itemsPerPage);
        }

        $outOfBoundsMetadata = $metadataOf($outOfBoundsPage);

        self::assertEmpty($idsOf($outOfBoundsPage));
        self::assertSame($totalItems, $outOfBoundsMetadata->totalItems);
        self::assertSame($lastPage, $outOfBoundsMetadata->lastPage);
        self::assertSame($lastPage + 1, $outOfBoundsMetadata->currentPage);
        self::assertSame($pageSize, $outOfBoundsMetadata->itemsPerPage);

        return $pages;
    }

    /**
     * Fetches one page from an empty collection, asserting the zero-item pagination metadata.
     *
     * @param \Closure(int, int): TPage           $askPage
     * @param \Closure(TPage): list<string>       $idsOf
     * @param \Closure(TPage): PaginationMetadata $metadataOf
     */
    protected function traverseEmptyPage(
        \Closure $askPage,
        \Closure $idsOf,
        \Closure $metadataOf,
        int $itemsPerPage,
    ): void {
        $page = $askPage(1, $itemsPerPage);
        $metadata = $metadataOf($page);

        self::assertEmpty($idsOf($page));
        self::assertSame(0, $metadata->totalItems);
        self::assertSame(1, $metadata->currentPage);
        self::assertSame($itemsPerPage, $metadata->itemsPerPage);
        self::assertSame(1, $metadata->lastPage);
    }
}
