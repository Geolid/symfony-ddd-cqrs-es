<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Enum\AppIdentityStatus;
use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<IdentityResult>
 *
 * @phpstan-type Row array{id: string, status: string, registered_at: string}
 */
final class DbalIdentityFinder extends AbstractDbalFinder implements IdentityFinderInterface
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
        $qb->select('id', 'status', 'registered_at')->from(DbalIdentityProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): IdentityResult
    {
        return new IdentityResult(
            id: $row['id'],
            status: AppIdentityStatus::from($row['status']),
            registeredAt: new \DateTimeImmutable($row['registered_at'], new \DateTimeZone('UTC')),
        );
    }
}
