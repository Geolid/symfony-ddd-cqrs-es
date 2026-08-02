<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenCredentialProjector;
use Webmozart\Assert\Assert;

final readonly class DbalApiTokenCredentialFinder implements ApiTokenCredentialFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function getByIdentifier(string $identifier): ?ApiTokenCredentialResult
    {
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
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): ApiTokenCredentialResult
    {
        Assert::string($row['id']);
        Assert::string($row['identity_id']);
        Assert::string($row['identifier']);
        Assert::string($row['hash']);
        Assert::string($row['expires_at']);

        return new ApiTokenCredentialResult(
            id: (string) $row['id'],
            identityId: (string) $row['identity_id'],
            identifier: (string) $row['identifier'],
            hash: (string) $row['hash'],
            revoked: (bool) $row['revoked'],
            expiresAt: new \DateTimeImmutable((string) $row['expires_at'], new \DateTimeZone('UTC')),
        );
    }
}
