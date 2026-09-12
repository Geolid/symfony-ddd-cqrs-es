<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemResult;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartItemProjector;

/**
 * @extends AbstractDbalFinder<CartItemResult>
 */
final class DbalCartItemFinder extends AbstractDbalFinder implements CartItemFinderInterface
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
        $qb->select('cart_id', 'product_id', 'quantity')
            ->from(DbalCartItemProjector::TABLE)
            ->orderBy('cart_id', 'ASC')
            ->addOrderBy('product_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CartItemResult::class;
    }
}
