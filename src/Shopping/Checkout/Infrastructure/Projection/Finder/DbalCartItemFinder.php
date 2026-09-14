<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\CartItem\CartItemResult;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartItemProjector;

/**
 * @extends AbstractIterableDbalFinder<CartItemResult>
 */
final class DbalCartItemFinder extends AbstractIterableDbalFinder implements CartItemFinderInterface
{
    public function byCart(string $cartId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cartId): void {
                $qb->andWhere('cart_id = :cartId')->setParameter('cartId', $cartId);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('cart_id', 'product_id', 'quantity')
            ->from(DbalCartItemProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['cart_id' => SortDirection::Ascending, 'added_at' => SortDirection::Ascending, 'product_id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return CartItemResult::class;
    }
}
