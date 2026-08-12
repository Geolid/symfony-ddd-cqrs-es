<?php

declare(strict_types=1);

namespace Iam\Access\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Infrastructure\Persistence\Projection\Projector\DbalGrantProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<GrantResult>
 *
 * @phpstan-type Row array{id: string, identity_id: string, permission: string}
 */
final class DbalGrantFinder extends AbstractDbalCollectionFinder implements GrantFinderInterface
{
    public function byIdentity(string ...$identityIds): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityIds) {
                $qb->andWhere($qb->expr()->in('identity_id', ':identityIds'))
                    ->setParameter('identityIds', $identityIds, ArrayParameterType::STRING);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'permission')
            ->from(DbalGrantProjector::TABLE)
            ->andWhere('revoked = 0');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): GrantResult
    {
        return new GrantResult(
            id: $row['id'],
            identityId: $row['identity_id'],
            permission: $row['permission'],
        );
    }
}
