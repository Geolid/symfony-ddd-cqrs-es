<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Sales\Order\Application\Exception\ProductChangedException;
use Sales\Order\Application\Exception\ProductNotAvailableException;
use Sales\Order\Application\Finder\Product\ProductAvailabilityFinderInterface;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalProductAvailabilityProjector;

final readonly class DbalProductAvailabilityFinder implements ProductAvailabilityFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function ensureAvailable(string $productId, string $label, int $unitAmountInCents): void
    {
        /** @var array{label: string, unit_amount_in_cents: int|string}|false $row */
        $row = $this->connection->createQueryBuilder()
            ->select('label', 'unit_amount_in_cents')
            ->from(DbalProductAvailabilityProjector::TABLE)
            ->andWhere('product_id = :productId')
            ->setParameter('productId', $productId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw ProductNotAvailableException::forId($productId);
        }

        if ($row['label'] !== $label || (int) $row['unit_amount_in_cents'] !== $unitAmountInCents) {
            throw ProductChangedException::forId($productId);
        }
    }
}
