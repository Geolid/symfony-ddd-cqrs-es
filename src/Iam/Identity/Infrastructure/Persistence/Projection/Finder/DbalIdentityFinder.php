<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<IdentityResult>
 *
 * @phpstan-type Row array{id: string, status: string, registered_at: string, erased_at: ?string}
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
            throw ResultNotFoundException::forId(Identity::class, $id);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'status', 'registered_at', 'erased_at')->from(DbalIdentityProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): IdentityResult
    {
        return new IdentityResult(
            id: $row['id'],
            status: IdentityStatus::from($row['status']),
            registeredAt: new \DateTimeImmutable($row['registered_at'], new \DateTimeZone('UTC')),
            erasedAt: null !== $row['erased_at'] ? new \DateTimeImmutable($row['erased_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
