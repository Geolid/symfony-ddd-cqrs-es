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
            \sprintf('SELECT id, shopper_id, lines_json, total_amount_in_cents FROM %s WHERE id = :id', DbalCartProjector::TABLE),
            ['id' => $id],
        );

        if (false === $row) {
            throw CartResultNotFoundException::forId($id);
        }

        \assert(\is_string($row['id']) && \is_string($row['shopper_id']) && \is_string($row['lines_json']) && is_numeric($row['total_amount_in_cents']));

        /** @var list<array{lineId: string, productId: string, label: string, unitPriceInCents: int, quantity: int}> $lines */
        $lines = json_decode($row['lines_json'], true, flags: \JSON_THROW_ON_ERROR);

        return new CartResult($row['id'], $row['shopper_id'], $lines, (int) $row['total_amount_in_cents']);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'shopper_id', 'total_amount_in_cents')
            ->from(DbalCartProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CartResult::class;
    }
}
