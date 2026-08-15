<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalBuyerProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<BuyerResult>
 *
 * @phpstan-type Row array{
 *     customer_id: string,
 *     shipping_first_name: string|null,
 *     shipping_last_name: string|null,
 *     shipping_street: string|null,
 *     shipping_postal_code: string|null,
 *     shipping_city: string|null,
 *     billing_first_name: string|null,
 *     billing_last_name: string|null,
 *     billing_street: string|null,
 *     billing_postal_code: string|null,
 *     billing_city: string|null,
 * }
 */
final class DbalBuyerFinder extends AbstractDbalFinder implements BuyerFinderInterface
{
    public function ofIdOrNull(string $customerId): ?BuyerResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('customer_id = :customerId')
            ->setParameter('customerId', $customerId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select(
            'customer_id',
            'shipping_first_name',
            'shipping_last_name',
            'shipping_street',
            'shipping_postal_code',
            'shipping_city',
            'billing_first_name',
            'billing_last_name',
            'billing_street',
            'billing_postal_code',
            'billing_city',
        )->from(DbalBuyerProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): BuyerResult
    {
        return new BuyerResult(
            customerId: $row['customer_id'],
            shippingAddress: $this->extractPostalAddress($row, 'shipping_'),
            billingAddress: $this->extractPostalAddress($row, 'billing_'),
        );
    }

    /**
     * @param array<string, string|null> $row
     *
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}|null
     */
    private function extractPostalAddress(array $row, string $prefix): ?array
    {
        if (!isset(
            $row[$prefix.'first_name'],
            $row[$prefix.'last_name'],
            $row[$prefix.'street'],
            $row[$prefix.'postal_code'],
            $row[$prefix.'city'],
        )) {
            return null;
        }

        return [
            'firstName' => $row[$prefix.'first_name'],
            'lastName' => $row[$prefix.'last_name'],
            'street' => $row[$prefix.'street'],
            'postalCode' => $row[$prefix.'postal_code'],
            'city' => $row[$prefix.'city'],
        ];
    }
}
