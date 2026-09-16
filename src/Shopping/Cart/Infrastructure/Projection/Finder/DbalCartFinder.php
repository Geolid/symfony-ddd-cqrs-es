<?php

declare(strict_types=1);

namespace Shopping\Cart\Infrastructure\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;
use Shopping\Cart\Application\CartStatus;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface;
use Shopping\Cart\Application\Finder\Cart\CartResult;
use Shopping\Cart\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Cart\Infrastructure\Projection\Projector\DbalCartProjector;

/**
 * @extends AbstractIterableDbalFinder<CartResult>
 */
final class DbalCartFinder extends AbstractIterableDbalFinder implements CartFinderInterface
{
    public function ofId(string $id): CartResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw CartResultNotFoundException::forId($id);
    }

    public function activeById(string ...$ids): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($ids): void {
                $qb->andWhere('id IN (:ids) AND status = :active')
                    ->setParameter('ids', $ids, ArrayParameterType::STRING)
                    ->setParameter('active', CartStatus::ACTIVE);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id', 'status', 'started_at')
            ->from(DbalCartProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['started_at' => SortDirection::Ascending, 'id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return CartResult::class;
    }
}
