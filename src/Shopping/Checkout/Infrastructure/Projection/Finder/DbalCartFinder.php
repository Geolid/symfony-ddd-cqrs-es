<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\CartResult;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartProjector;

/**
 * @extends AbstractDbalFinder<CartResult>
 */
final class DbalCartFinder extends AbstractDbalFinder implements CartFinderInterface
{
    public function ofId(string $id): CartResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw CartResultNotFoundException::forId($id);
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id', 'started_at')
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
