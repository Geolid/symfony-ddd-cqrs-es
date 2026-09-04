<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Sales\OrderSummary\Infrastructure\Projection\Projector\DbalOrderSummaryProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderSummaryResult>
 */
final class DbalOrderSummaryFinder extends AbstractDbalFinder implements OrderSummaryFinderInterface
{
    public function ofOrder(string $orderId): OrderSummaryResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one() ?? throw OrderSummaryResultNotFoundException::forOrder($orderId);
    }

    public function byCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId): void {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    public function byStatus(OrderSummaryStatus $status): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($status): void {
                $qb->andWhere('status = :status')
                    ->setParameter('status', $status);
            },
        );
    }

    public function sortedByPlacedAt(): static
    {
        return $this->filter(static function (QueryBuilder $qb): void {
            $qb->orderBy('placed_at', 'DESC')->addOrderBy('order_id', 'DESC');
        });
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select(
            'order_id',
            'customer_id',
            'total_amount_in_cents',
            'status',
            'placed_at',
            'cancelled_at',
            'payment_amount_in_cents',
            'payment_reference',
            'payment_checkout_url',
            'paid_at',
            'tracking_number',
            'dispatched_at',
            'delivered_at',
        )
            ->from(DbalOrderSummaryProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderSummaryResult::class;
    }
}
