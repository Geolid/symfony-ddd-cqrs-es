<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionResult;
use Shopping\Checkout\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;

/**
 * @extends AbstractDbalFinder<CheckoutSessionResult>
 */
final class DbalCheckoutSessionFinder extends AbstractDbalFinder implements CheckoutSessionFinderInterface
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

    public function ofCartOrNull(string $cartId): ?CheckoutSessionResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cartId): void {
                $qb->andWhere('cart_id = :cartId')->setParameter('cartId', $cartId);
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
                $cutoffParam = $qb->createNamedParameter($cutoff, Types::DATETIME_IMMUTABLE);
                $openParam = $qb->createNamedParameter(CheckoutSessionStatus::OPEN);

                $qb->andWhere("status = {$openParam} AND opened_at < {$cutoffParam}");
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'cart_id', 'customer_id', 'shipping_address', 'billing_address', 'total_amount_in_cents', 'status', 'opened_at')
            ->from(DbalCheckoutSessionProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CheckoutSessionResult::class;
    }
}
