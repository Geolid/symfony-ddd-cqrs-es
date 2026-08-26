<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PasswordCredentialResult>
 */
final class DbalPasswordCredentialFinder extends AbstractDbalFinder implements PasswordCredentialFinderInterface
{
    public function ofLogin(string $login): PasswordCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($login): void {
                $qb->andWhere('login = :login')->setParameter('login', $login);
            },
        )->one() ?? throw PasswordCredentialResultNotFoundException::forLogin($login);
    }

    public function ofIdentityId(string $identityId): PasswordCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId')->setParameter('identityId', $identityId);
            },
        )->one() ?? throw PasswordCredentialResultNotFoundException::forIdentity($identityId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'login', 'password_hash', 'defined_at', 'password_changed_at', 'identity_authenticatable')
            ->from(DbalPasswordCredentialProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PasswordCredentialResult::class;
    }
}
