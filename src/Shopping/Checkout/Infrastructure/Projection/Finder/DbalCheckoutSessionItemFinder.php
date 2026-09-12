<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;
use Shopping\Checkout\Application\Finder\CheckoutSessionItem\CheckoutSessionItemFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSessionItem\CheckoutSessionItemResult;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionItemProjector;

/**
 * @extends AbstractDbalFinder<CheckoutSessionItemResult>
 */
final class DbalCheckoutSessionItemFinder extends AbstractDbalFinder implements CheckoutSessionItemFinderInterface
{
    public function byCheckoutSession(string $checkoutSessionId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($checkoutSessionId): void {
                $qb->andWhere('checkout_session_id = :checkoutSessionId')->setParameter('checkoutSessionId', $checkoutSessionId);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('checkout_session_id', 'product_id', 'label', 'unit_price_in_cents', 'quantity')
            ->from(DbalCheckoutSessionItemProjector::TABLE)
            ->orderBy('checkout_session_id', 'ASC')
            ->addOrderBy('product_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CheckoutSessionItemResult::class;
    }
}
