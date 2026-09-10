<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Ordering\Application\Finder\Cart\CartFinderInterface;
use Sales\Ordering\Application\Finder\Cart\CartResult;
use Sales\Ordering\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCartProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

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

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'buyer_id', 'status', 'line_items')
            ->from(DbalCartProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CartResult::class;
    }
}
