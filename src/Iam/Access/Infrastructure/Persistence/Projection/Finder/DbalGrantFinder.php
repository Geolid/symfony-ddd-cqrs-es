<?php

declare(strict_types=1);

namespace Iam\Access\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Access\Infrastructure\Persistence\Projection\Projector\DbalGrantProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<GrantResult>
 *
 * @phpstan-type Row array{id: string, identity_id: string, permission: string, revoked: string|int}
 */
final class DbalGrantFinder extends AbstractDbalFinder implements GrantFinderInterface
{
    public function forIdentity(string $identityId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId) {
                $qb->andWhere('identity_id = :identityId')
                    ->setParameter('identityId', $identityId);
            },
        );
    }

    public function withoutRevoked(): static
    {
        return $this->filter(static function (QueryBuilder $qb): void {
            $qb->andWhere('revoked = 0');
        });
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'permission', 'revoked')
            ->from(DbalGrantProjector::TABLE);
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
            revoked: (bool) $row['revoked'],
        );
    }
}
