<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Patchlevel\Hydrator\Hydrator;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractPaginatableDbalFinder;

/**
 * @extends AbstractPaginatableDbalFinder<DummyHydratable>
 */
final class FakeDbalFinder extends AbstractPaginatableDbalFinder
{
    /**
     * @param list<string> $values
     */
    public function __construct(
        Connection $connection,
        Hydrator $hydrator,
        public array $values,
    ) {
        parent::__construct($connection, $hydrator);
    }

    public function ofValue(string $value): ?DummyHydratable
    {
        return $this->byValue($value)->one();
    }

    public function byValue(string $value): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($value): void {
            $qb->andWhere('string = :value')->setParameter('value', $value);
        });
    }

    public function sortedBy(SortDirection $direction): static
    {
        return $this->sortBy(['string' => $direction]);
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $rows = array_map(fn (string $value): string => \sprintf('SELECT %s', $this->connection->quote($value)), $this->values);
        $source = implode(' UNION ALL ', ["SELECT '' AS string FROM DUAL WHERE 1 = 0", ...$rows]);

        $qb->select('string')->from(\sprintf('(%s)', $source), 'fake');
    }

    protected function resultClass(): string
    {
        return DummyHydratable::class;
    }

    protected function defaultSort(): array
    {
        return ['string' => SortDirection::Ascending];
    }
}
