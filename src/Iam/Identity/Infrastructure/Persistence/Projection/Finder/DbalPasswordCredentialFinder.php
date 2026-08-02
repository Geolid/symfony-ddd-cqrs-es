<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;

/**
 * @phpstan-type Row array{id: string, identity_id: string, login: string, hash: string}
 */
final readonly class DbalPasswordCredentialFinder implements PasswordCredentialFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function getByLogin(string $login): ?PasswordCredentialResult
    {
        /** @var Row|false $row */
        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT id, identity_id, login, hash FROM %s WHERE login = :login', DbalPasswordCredentialProjector::TABLE),
            ['login' => $login],
        );

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @param Row $row
     */
    private function mapRow(array $row): PasswordCredentialResult
    {
        return new PasswordCredentialResult(
            id: $row['id'],
            identityId: $row['identity_id'],
            login: $row['login'],
            hash: $row['hash'],
        );
    }
}
