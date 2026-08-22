<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<IdentityResult>
 *
 * @phpstan-type Row array{id: string, status: string, reason: string|null, registered_at: string, suspended_at: string|null, reactivated_at: string|null}
 */
final class DbalIdentityFinder extends AbstractDbalCollectionFinder implements IdentityFinderInterface
{
    public function ofId(string $id): IdentityResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw IdentityResultNotFoundException::forId($id);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'status', 'reason', 'registered_at', 'suspended_at', 'reactivated_at')
            ->from(DbalIdentityProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): IdentityResult
    {
        return new IdentityResult(
            id: $row['id'],
            status: IdentityStatus::from($row['status']),
            reason: $row['reason'],
            registeredAt: new \DateTimeImmutable($row['registered_at'], new \DateTimeZone('UTC')),
            suspendedAt: null !== $row['suspended_at'] ? new \DateTimeImmutable($row['suspended_at'], new \DateTimeZone('UTC')) : null,
            reactivatedAt: null !== $row['reactivated_at'] ? new \DateTimeImmutable($row['reactivated_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
