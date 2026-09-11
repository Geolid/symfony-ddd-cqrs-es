<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Ordering\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Sales\Ordering\Application\Finder\CheckoutSession\CheckoutSessionResult;
use Sales\Ordering\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCheckoutSessionProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<CheckoutSessionResult>
 */
final class DbalCheckoutSessionFinder extends AbstractDbalFinder implements CheckoutSessionFinderInterface
{
    public function ofId(string $checkoutSessionId): CheckoutSessionResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($checkoutSessionId): void {
                $qb->andWhere('checkout_session_id = :checkoutSessionId')->setParameter('checkoutSessionId', $checkoutSessionId);
            },
        )->one() ?? throw CheckoutSessionResultNotFoundException::forId($checkoutSessionId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('checkout_session_id', 'cart_id', 'shopper_id', 'shipping_address', 'billing_address')
            ->from(DbalCheckoutSessionProjector::TABLE)
            ->orderBy('checkout_session_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return CheckoutSessionResult::class;
    }
}
