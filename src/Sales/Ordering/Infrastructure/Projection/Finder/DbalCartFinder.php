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
    public function ofId(string $cartId): CartResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cartId): void {
                $qb->andWhere('cart_id = :cartId')->setParameter('cartId', $cartId);
            },
        )->one() ?? throw CartResultNotFoundException::forId($cartId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('cart_id', 'shopper_id')
            ->from(DbalCartProjector::TABLE)
            ->orderBy('cart_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CartResult::class;
    }
}
