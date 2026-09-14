<?php

declare(strict_types=1);

namespace Shopping\Cart\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;
use Shopping\Cart\Application\CartStatus;
use Shopping\Cart\Application\Finder\Cart\CartFinderInterface;
use Shopping\Cart\Application\Finder\Cart\CartResult;
use Shopping\Cart\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Cart\Infrastructure\Projection\Projector\DbalCartItemProjector;
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

    public function byProductId(string $productId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($productId): void {
                $activeParam = $qb->createNamedParameter(CartStatus::ACTIVE->value);
                $productIdParam = $qb->createNamedParameter($productId);

                $qb->andWhere(\sprintf(
                    'id IN (SELECT cart_id FROM %s WHERE product_id = %s) AND status = %s',
                    DbalCartItemProjector::TABLE,
                    $productIdParam,
                    $activeParam,
                ));
            },
        );
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
