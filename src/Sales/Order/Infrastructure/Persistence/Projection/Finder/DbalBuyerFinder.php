<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Order\Application\Finder\Buyer\BuyerResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalBuyerProjector;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
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
    public function ofId(string $customerId): ?BuyerResult
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
            shippingAddress: $this->postalAddressOf($row['shipping_first_name'], $row['shipping_last_name'], $row['shipping_street'], $row['shipping_postal_code'], $row['shipping_city']),
            billingAddress: $this->postalAddressOf($row['billing_first_name'], $row['billing_last_name'], $row['billing_street'], $row['billing_postal_code'], $row['billing_city']),
        );
    }

    private function postalAddressOf(?string $firstName, ?string $lastName, ?string $street, ?string $postalCode, ?string $city): ?PostalAddress
    {
        if (null === $firstName || null === $lastName || null === $street || null === $postalCode || null === $city) {
            return null;
        }

        return PostalAddress::of(FullName::of($firstName, $lastName), Address::of($street, $postalCode, $city));
    }
}
