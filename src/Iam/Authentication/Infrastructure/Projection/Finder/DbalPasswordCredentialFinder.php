<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalPasswordCredentialProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PasswordCredentialResult>
 */
final class DbalPasswordCredentialFinder extends AbstractDbalFinder implements PasswordCredentialFinderInterface
{
    public function ofIdentityOrNull(string $identityId): ?PasswordCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId')->setParameter('identityId', $identityId);
            },
        )->one();
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'password_hash', 'defined_at', 'changed_at')
            ->from(DbalPasswordCredentialProjector::TABLE);
    }

    protected function resultClass(): string
    {
        return PasswordCredentialResult::class;
    }
}
