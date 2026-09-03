<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
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

    #[Test]
    public function itMemoizesTotalItems(): void
    {
        // Given
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('fetchOne')->willReturn(5);

        $connection = $this->createMock(Connection::class);
        $connection->method('createQueryBuilder')->willReturn($this->queryBuilder());
        $connection->expects(self::once())->method('executeQuery')->willReturn($result);

        $paginator = new DbalPaginator(
            $connection,
            static fn (): QueryBuilder => $connection->createQueryBuilder(),
            static fn (): object => new \stdClass(),
        );

        // When
        $firstCount = $paginator->totalItems();
        $secondCount = $paginator->totalItems();

        // Then
        self::assertSame(5, $firstCount);
        self::assertSame(5, $secondCount);
    }

    /**
     * @return DbalPaginator<\stdClass>
     */
    private function paginator(int $totalItems, int $page = 1, int $itemsPerPage = 20): DbalPaginator
    {
        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn($totalItems);

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        return new DbalPaginator(
            $connection,
            fn (): QueryBuilder => $this->queryBuilder(),
            static fn (): object => new \stdClass(),
            $page,
            $itemsPerPage,
        );
    }

    private function queryBuilder(): QueryBuilder
    {
        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('getSQL')->willReturn('SELECT 1');
        $qb->method('getParameters')->willReturn([]);
        $qb->method('getParameterTypes')->willReturn([]);
        $qb->method('resetOrderBy')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();

        return $qb;
    }
}
