<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenCredentialProjector;

/**
 * @phpstan-type Row array{id: string, identity_id: string, identifier: string, hash: string, revoked: string|int, expires_at: string}
 */
final readonly class DbalApiTokenCredentialFinder implements ApiTokenCredentialFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function ofIdentifier(string $identifier): ?ApiTokenCredentialResult
    {
        /** @var Row|false $row */
        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT id, identity_id, identifier, hash, revoked, expires_at FROM %s WHERE identifier = :identifier', DbalApiTokenCredentialProjector::TABLE),
            ['identifier' => $identifier],
        );

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @param Row $row
     */
    private function mapRow(array $row): ApiTokenCredentialResult
    {
        return new ApiTokenCredentialResult(
            id: $row['id'],
            identityId: $row['identity_id'],
            identifier: $row['identifier'],
            hash: $row['hash'],
            revoked: (bool) $row['revoked'],
            expiresAt: new \DateTimeImmutable($row['expires_at'], new \DateTimeZone('UTC')),
        );
    }
}
