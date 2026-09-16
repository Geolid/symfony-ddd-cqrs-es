<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;

/**
 * @extends AbstractIterableDbalFinder<CheckoutSessionResult>
 */
final class DbalCheckoutSessionFinder extends AbstractIterableDbalFinder implements CheckoutSessionFinderInterface
{
    /**
     * @throws CheckoutSessionResultNotFoundException
     */
    public function ofId(string $id): CheckoutSessionResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw CheckoutSessionResultNotFoundException::forId($id);
    }

    public function openOfCartOrNull(string $cartId): ?CheckoutSessionResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cartId): void {
                $qb->andWhere('cart_id = :cartId AND status = :open')
                    ->setParameter('cartId', $cartId)
                    ->setParameter('open', CheckoutSessionStatus::OPEN);
            },
        )->one();
    }

    public function byCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId): void {
                $qb->andWhere('customer_id = :customerId')->setParameter('customerId', $customerId);
            },
        );
    }

    public function stalledBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('status = :open AND opened_at < :cutoff')
                    ->setParameter('open', CheckoutSessionStatus::OPEN)
                    ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'cart_id', 'customer_id', 'items', 'shipping_address', 'billing_address', 'total_excluding_tax_in_cents', 'total_tax_amount_in_cents', 'total_including_tax_in_cents', 'currency', 'tax_rate_basis_points', 'status', 'opened_at')
            ->from(DbalCheckoutSessionProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['opened_at' => SortDirection::Ascending, 'id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return CheckoutSessionResult::class;
    }
}
