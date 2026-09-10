<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Ordering\Application\Finder\CartLine\CartLineFinderInterface;
use Sales\Ordering\Application\Finder\CartLine\CartLineResult;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCartLineProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<CartLineResult>
 */
final class DbalCartLineFinder extends AbstractDbalFinder implements CartLineFinderInterface
{
    public function byCart(string $cartId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cartId): void {
                $qb->andWhere('cart_id = :cartId')->setParameter('cartId', $cartId);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('line_id', 'product_id', 'label', 'unit_price_in_cents', 'quantity')
            ->from(DbalCartLineProjector::TABLE)
            ->orderBy('line_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CartLineResult::class;
    }
}
