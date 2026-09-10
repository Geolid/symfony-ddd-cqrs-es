<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;
use Shopping\Checkout\Application\Finder\Shopper\Exception\ShopperResultNotFoundException;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Application\Finder\Shopper\ShopperResult;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalShopperProjector;

/**
 * @extends AbstractDbalFinder<ShopperResult>
 */
final class DbalShopperFinder extends AbstractDbalFinder implements ShopperFinderInterface
{
    public function ofId(string $id): ShopperResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw ShopperResultNotFoundException::forId($id);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'email', 'registered_at', 'erasure_status')
            ->from(DbalShopperProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ShopperResult::class;
    }
}
