<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\Finder\Payment\PaymentResult;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Infrastructure\Projection\Projector\DbalPaymentProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PaymentResult>
 */
final class DbalPaymentFinder extends AbstractDbalFinder implements PaymentFinderInterface
{
    public function ofId(string $id): PaymentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw PaymentResultNotFoundException::forId($id);
    }

    public function ofReference(string $reference): PaymentResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($reference): void {
                $qb->andWhere('reference = :reference')->setParameter('reference', $reference);
            },
        )->one() ?? throw PaymentResultNotFoundException::forReference($reference);
    }

    public function byStatus(PaymentStatus $status): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($status): void {
                $qb->andWhere('status = :status')->setParameter('status', $status);
            },
        );
    }

    public function stalledBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $cutoffParam = $qb->createNamedParameter($cutoff, Types::DATETIME_IMMUTABLE);
                $requestedParam = $qb->createNamedParameter(PaymentStatus::REQUESTED);

                $qb->andWhere("status = {$requestedParam} AND requested_at < {$cutoffParam}");
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'amount_in_cents', 'reference', 'checkout_url', 'status', 'requested_at', 'authorized_at', 'captured_at', 'failed_at', 'cancelled_at', 'refund_requested_at', 'refund_failed_at', 'refunded_at')
            ->from(DbalPaymentProjector::TABLE)
            ->orderBy('requested_at', 'ASC')
            ->addOrderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PaymentResult::class;
    }
}
