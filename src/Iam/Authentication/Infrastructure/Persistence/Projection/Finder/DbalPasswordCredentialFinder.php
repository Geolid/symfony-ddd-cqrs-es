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
 *
 * @phpstan-type Row array{id: string, identity_id: string, login: string, password_hash: string, identity_authenticatable: bool}
 */
final class DbalPasswordCredentialFinder extends AbstractDbalFinder implements PasswordCredentialFinderInterface
{
    public function ofLogin(string $login): PasswordCredentialResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('login = :login')
            ->setParameter('login', $login)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw PasswordCredentialResultNotFoundException::forLogin($login);
        }

        return $this->mapRow($row);
    }

    public function ofIdentityId(string $identityId): PasswordCredentialResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('identity_id = :identityId')
            ->setParameter('identityId', $identityId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw PasswordCredentialResultNotFoundException::forIdentity($identityId);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'login', 'password_hash', 'identity_authenticatable')->from(DbalPasswordCredentialProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): PasswordCredentialResult
    {
        return new PasswordCredentialResult(
            id: $row['id'],
            identityId: $row['identity_id'],
            login: $row['login'],
            passwordHash: $row['password_hash'],
            identityAuthenticatable: (bool) $row['identity_authenticatable'],
        );
    }
}
