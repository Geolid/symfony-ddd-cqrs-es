<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Fulfilment\Shipping\Application\Finder\PaymentCapture\PaymentCaptureFinderInterface;
use Fulfilment\Shipping\Application\Finder\PaymentCapture\PaymentCaptureResult;
use Fulfilment\Shipping\Infrastructure\Projection\Projector\DbalPaymentCaptureProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PaymentCaptureResult>
 */
final class DbalPaymentCaptureFinder extends AbstractDbalFinder implements PaymentCaptureFinderInterface
{
    public function ofOrderOrNull(string $orderId): ?PaymentCaptureResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'captured')
            ->from(DbalPaymentCaptureProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PaymentCaptureResult::class;
    }
}
