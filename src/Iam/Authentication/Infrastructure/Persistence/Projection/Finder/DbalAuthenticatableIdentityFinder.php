<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\AuthenticatableIdentity\AuthenticatableIdentityFinderInterface;
use Iam\Authentication\Application\Finder\AuthenticatableIdentity\AuthenticatableIdentityResult;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalAuthenticatableIdentityProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<AuthenticatableIdentityResult>
 *
 * @phpstan-type Row array{identity_id: string, authenticatable: bool}
 */
final class DbalAuthenticatableIdentityFinder extends AbstractDbalFinder implements AuthenticatableIdentityFinderInterface
{
    public function ofIdentityId(string $identityId): AuthenticatableIdentityResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('identity_id = :identityId')
            ->setParameter('identityId', $identityId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw AuthenticatableIdentityResultNotFoundException::forIdentity($identityId);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('identity_id', 'authenticatable')->from(DbalAuthenticatableIdentityProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): AuthenticatableIdentityResult
    {
        return new AuthenticatableIdentityResult(
            identityId: $row['identity_id'],
            authenticatable: (bool) $row['authenticatable'],
        );
    }
}
