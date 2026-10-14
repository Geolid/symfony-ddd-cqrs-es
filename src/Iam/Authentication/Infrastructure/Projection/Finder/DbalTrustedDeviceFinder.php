<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalTrustedDeviceProjector;
use Patchlevel\Hydrator\Hydrator;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractIterableDbalFinder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends AbstractIterableDbalFinder<TrustedDeviceResult>
 */
final class DbalTrustedDeviceFinder extends AbstractIterableDbalFinder implements TrustedDeviceFinderInterface
{
    public function __construct(
        Connection $connection,
        #[Autowire(service: 'shared.hydration.hydrator')]
        Hydrator $hydrator,
    ) {
        parent::__construct($connection, $hydrator);
    }

    public function activeByIdentity(string $identityId, \DateTimeImmutable $now): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId, $now): void {
                $qb->andWhere('identity_id = :identityId AND revoked_at IS NULL AND expires_at >= :now')
                    ->setParameter('identityId', $identityId)
                    ->setParameter('now', $now, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'user_agent', 'ip', 'trusted_at')
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
