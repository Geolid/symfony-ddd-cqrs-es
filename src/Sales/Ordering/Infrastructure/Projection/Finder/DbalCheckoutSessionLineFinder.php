<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Ordering\Application\Finder\CheckoutSessionLine\CheckoutSessionLineFinderInterface;
use Sales\Ordering\Application\Finder\CheckoutSessionLine\CheckoutSessionLineResult;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCheckoutSessionLineProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<CheckoutSessionLineResult>
 */
final class DbalCheckoutSessionLineFinder extends AbstractDbalFinder implements CheckoutSessionLineFinderInterface
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
        $qb->select('line_id', 'checkout_session_id', 'product_id', 'label', 'unit_price_in_cents', 'quantity')
            ->from(DbalCheckoutSessionLineProjector::TABLE)
            ->orderBy('opened_at', 'ASC')
            ->addOrderBy('line_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CheckoutSessionLineResult::class;
    }
}
