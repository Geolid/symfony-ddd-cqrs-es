<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalBackupCodeCredentialProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<BackupCodeCredentialResult>
 */
final class DbalBackupCodeCredentialFinder extends AbstractDbalFinder implements BackupCodeCredentialFinderInterface
{
    public function ofIdentityOrNull(string $identityId): ?BackupCodeCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId')->setParameter('identityId', $identityId);
            },
        )->one();
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('identity_id', 'generated_at', 'regenerated_at', 'remaining_count')->from(DbalBackupCodeCredentialProjector::TABLE);
    }

    protected function resultClass(): string
    {
        return BackupCodeCredentialResult::class;
    }
}
