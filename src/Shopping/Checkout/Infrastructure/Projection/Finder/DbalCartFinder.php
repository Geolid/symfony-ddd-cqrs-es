<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
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
        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT id, customer_id FROM %s WHERE id = :id', DbalCartProjector::TABLE),
            ['id' => $id],
        );

        if (false === $row) {
            throw CartResultNotFoundException::forId($id);
        }

        \assert(\is_string($row['id']) && \is_string($row['customer_id']));

        return new CartResult($row['id'], $row['customer_id']);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id')
            ->from(DbalCartProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CartResult::class;
    }
}
