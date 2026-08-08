<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenCredentialProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ApiTokenCredentialResult>
 *
 * @phpstan-type Row array{id: string, identity_id: string, identifier: string, hash: string, revoked: string|int, expires_at: string, identity_status: string}
 */
final class DbalApiTokenCredentialFinder extends AbstractDbalFinder implements ApiTokenCredentialFinderInterface
{
    public function ofIdentifier(string $identifier): ApiTokenCredentialResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw ApiTokenCredentialResultNotFoundException::forIdentifier($identifier);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'identifier', 'hash', 'revoked', 'expires_at', 'identity_status')
            ->from(DbalApiTokenCredentialProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): ApiTokenCredentialResult
    {
        return new ApiTokenCredentialResult(
            id: $row['id'],
            identityId: $row['identity_id'],
            identifier: $row['identifier'],
            hash: $row['hash'],
            revoked: (bool) $row['revoked'],
            expiresAt: new \DateTimeImmutable($row['expires_at'], new \DateTimeZone('UTC')),
            identityStatus: IdentityStatus::from($row['identity_status']),
        );
    }
}
