<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustFinderInterface;
use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalDeviceTrustProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<DeviceTrustResult>
 */
final class DbalDeviceTrustFinder extends AbstractDbalFinder implements DeviceTrustFinderInterface
{
    public function ofIdentityOrNull(string $identityId): ?DeviceTrustResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId')->setParameter('identityId', $identityId);
            },
        )->one();
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('identity_id', 'revoked_at')->from(DbalDeviceTrustProjector::TABLE);
    }

    protected function resultClass(): string
    {
        return DeviceTrustResult::class;
    }
}
