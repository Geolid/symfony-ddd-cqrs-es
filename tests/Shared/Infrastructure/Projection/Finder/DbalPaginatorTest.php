<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Projection\Finder\DbalPaginator;

final class DbalPaginatorTest extends TestCase
{
    #[Test]
    #[DataProvider('provideInvalidPagination')]
    public function itThrowsWithInvalidPagination(int $page, int $itemsPerPage): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $this->paginator(0, $page, $itemsPerPage);
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function provideInvalidPagination(): iterable
    {
        yield 'zero page' => [0, 10];
        yield 'negative page' => [-1, 10];
        yield 'zero items per page' => [1, 0];
        yield 'negative items per page' => [1, -5];
    }

    #[Test]
    #[DataProvider('provideLastPages')]
    public function itComputesLastPage(int $total, int $itemsPerPage, int $expected): void
    {
        // Given
        $paginator = $this->paginator($total, 1, $itemsPerPage);

        // When
        $lastPage = $paginator->lastPage();

        // Then
        self::assertSame($expected, $lastPage);
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function provideLastPages(): iterable
    {
        yield 'no items' => [0, 5, 1];
        yield 'evenly divided' => [4, 2, 2];
        yield 'partial last page' => [5, 2, 3];
        yield 'fits on one page' => [3, 10, 1];
    }

    #[Test]
    #[DataProvider('provideCurrentPages')]
    public function itCountsCurrentPage(int $total, int $itemsPerPage, int $page, int $expected): void
    {
        // Given
        $paginator = $this->paginator($total, $page, $itemsPerPage);

        // When
        $count = $paginator->count();

        // Then
        self::assertSame($expected, $count);
    }

    /**
     * @return iterable<string, array{int, int, int, int}>
     */
    public static function provideCurrentPages(): iterable
    {
        yield 'full page' => [5, 2, 1, 2];
        yield 'partial last page' => [5, 2, 3, 1];
        yield 'out of bounds' => [5, 2, 10, 0];
        yield 'no items' => [0, 5, 1, 0];
    }

    /**
     * @return DbalPaginator<\stdClass>
     */
    private function paginator(int $totalItems, int $page = 1, int $itemsPerPage = 20): DbalPaginator
    {
        return new DbalPaginator(
            static fn (): QueryBuilder => throw new \LogicException('Not exercised by this test.'),
            static fn (): object => new \stdClass(),
            static fn (): int => $totalItems,
            $page,
            $itemsPerPage,
        );
    }
}
