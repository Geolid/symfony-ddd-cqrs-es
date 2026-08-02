<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalIdentityProjector;
use Webmozart\Assert\Assert;

final readonly class DbalIdentityFinder implements IdentityFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function getById(string $id): ?IdentityResult
    {
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
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): IdentityResult
    {
        Assert::string($row['id']);
        Assert::string($row['status']);
        Assert::string($row['registered_at']);

        return new IdentityResult(
            id: (string) $row['id'],
            status: (string) $row['status'],
            registeredAt: new \DateTimeImmutable((string) $row['registered_at'], new \DateTimeZone('UTC')),
        );
    }
}
