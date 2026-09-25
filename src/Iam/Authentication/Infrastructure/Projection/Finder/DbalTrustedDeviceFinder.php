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
use Psr\Clock\ClockInterface;
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
        private readonly ClockInterface $clock,
        #[Autowire(param: 'iam.authentication.trusted_device_lifetime')]
        private readonly int $lifetime,
    ) {
        parent::__construct($connection, $hydrator);
    }

    public function activeByIdentity(string $identityId): static
    {
        $cutoff = $this->clock->now()->modify(\sprintf('-%d seconds', $this->lifetime));

        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId, $cutoff): void {
                $qb->andWhere('identity_id = :identityId AND revoked_at IS NULL AND trusted_at >= :cutoff')
                    ->setParameter('identityId', $identityId)
                    ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE);
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
