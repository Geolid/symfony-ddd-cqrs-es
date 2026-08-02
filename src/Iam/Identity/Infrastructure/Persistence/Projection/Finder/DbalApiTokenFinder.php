<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Finder\ApiToken\ApiTokenCredentialResult;
use Iam\Identity\Application\Finder\ApiToken\ApiTokenFinderInterface;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalApiTokenProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ApiTokenCredentialResult>
 *
 * @phpstan-type Row array{id: string, identity_id: string, identifier: string, secret_hash: string, revoked: string|int}
 */
final class DbalApiTokenFinder extends AbstractDbalFinder implements ApiTokenFinderInterface
{
    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'identifier', 'secret_hash', 'revoked')
            ->from(DbalApiTokenProjector::TABLE);
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
            secretHash: $row['secret_hash'],
            revoked: (bool) $row['revoked'],
        );
    }
}
