<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractPaginatableDbalFinder;

/**
 * @extends AbstractPaginatableDbalFinder<IdentityResult>
 */
final class DbalIdentityFinder extends AbstractPaginatableDbalFinder implements IdentityFinderInterface
{
    public function ofId(string $id): IdentityResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw IdentityResultNotFoundException::forId($id);
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'status', 'reason', 'registered_at', 'suspended_at', 'reactivated_at', 'erasure_status')
            ->from(DbalIdentityProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['registered_at' => SortDirection::Ascending, 'id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return IdentityResult::class;
    }
}
