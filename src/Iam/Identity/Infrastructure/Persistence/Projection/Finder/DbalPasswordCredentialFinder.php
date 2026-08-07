<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PasswordCredentialResult>
 *
 * @phpstan-type Row array{id: string, identity_id: string, login: string, hash: string}
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

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'login', 'hash')->from(DbalPasswordCredentialProjector::TABLE);
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
            hash: $row['hash'],
        );
    }
}
