<?php

declare(strict_types=1);

namespace Compliance\Erasure\Infrastructure\Projection\Finder;

use Compliance\Erasure\Application\Finder\Subject\Exception\SubjectResultNotFoundException;
use Compliance\Erasure\Application\Finder\Subject\SubjectFinderInterface;
use Compliance\Erasure\Application\Finder\Subject\SubjectResult;
use Compliance\Erasure\Application\SubjectStatus;
use Compliance\Erasure\Infrastructure\Projection\Projector\DbalSubjectProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<SubjectResult>
 */
final class DbalSubjectFinder extends AbstractDbalFinder implements SubjectFinderInterface
{
    public function ofId(string $id): SubjectResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw SubjectResultNotFoundException::forId($id);
    }

    public function erasingBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('status = :erasingStatus')
                    ->andWhere('requested_at < :cutoff')
                    ->setParameter('erasingStatus', SubjectStatus::ERASING->value)
                    ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'status', 'requested_at', 'active_hold_count')
            ->from(DbalSubjectProjector::TABLE)
            ->orderBy('requested_at', 'ASC')
            ->addOrderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return SubjectResult::class;
    }
}
