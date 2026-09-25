<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalTrustedDeviceProjector;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;

/**
 * @extends AbstractIterableDbalFinder<TrustedDeviceResult>
 */
final class DbalTrustedDeviceFinder extends AbstractIterableDbalFinder implements TrustedDeviceFinderInterface
{
    public function activeByIdentity(string $identityId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId AND revoked_at IS NULL')
                    ->setParameter('identityId', $identityId);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'version', 'user_agent', 'ip', 'trusted_at')
            ->from(DbalTrustedDeviceProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['trusted_at' => SortDirection::Descending, 'id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return TrustedDeviceResult::class;
    }
}
