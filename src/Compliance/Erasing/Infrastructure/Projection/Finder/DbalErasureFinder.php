<?php

declare(strict_types=1);

namespace Compliance\Erasing\Infrastructure\Projection\Finder;

use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Finder\Erasure\ErasureResult;
use Compliance\Erasing\Application\Finder\Erasure\Exception\ErasureResultNotFoundException;
use Compliance\Erasing\Infrastructure\Projection\Projector\DbalErasureProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;

/**
 * @extends AbstractIterableDbalFinder<ErasureResult>
 */
final class DbalErasureFinder extends AbstractIterableDbalFinder implements ErasureFinderInterface
{
    public function ofId(string $id): ErasureResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw ErasureResultNotFoundException::forId($id);
    }

    public function requestedOfIdentityOrNull(string $identityId): ?ErasureResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId AND status = :requestedStatus')
                    ->setParameter('identityId', $identityId)
                    ->setParameter('requestedStatus', ErasureRequestStatus::REQUESTED->value);
            },
        )->one();
    }

    public function dueForApprovalBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('status = :requestedStatus')
                    ->andWhere('effective_at < :cutoff')
                    ->setParameter('requestedStatus', ErasureRequestStatus::REQUESTED->value)
                    ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'status', 'requested_at', 'effective_at', 'cancelled_at', 'approved_at')
            ->from(DbalErasureProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return ErasureResult::class;
    }
}
