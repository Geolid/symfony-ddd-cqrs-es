<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;

/**
 * @phpstan-type Row array{id: string, status: string, registered_at: string}
 */
final readonly class DbalIdentityFinder implements IdentityFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function ofId(string $id): ?IdentityResult
    {
        /** @var Row|false $row */
        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT id, status, registered_at FROM %s WHERE id = :id', DbalIdentityProjector::TABLE),
            ['id' => $id],
        );

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @param Row $row
     */
    private function mapRow(array $row): IdentityResult
    {
        return new IdentityResult(
            id: $row['id'],
            status: $row['status'],
            registeredAt: new \DateTimeImmutable($row['registered_at'], new \DateTimeZone('UTC')),
        );
    }
}
