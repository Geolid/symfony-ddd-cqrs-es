<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Webmozart\Assert\Assert;

final readonly class DbalPasswordCredentialFinder implements PasswordCredentialFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function getByLogin(string $login): ?PasswordCredentialResult
    {
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
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): PasswordCredentialResult
    {
        Assert::string($row['id']);
        Assert::string($row['identity_id']);
        Assert::string($row['login']);
        Assert::string($row['hash']);

        return new PasswordCredentialResult(
            id: (string) $row['id'],
            identityId: (string) $row['identity_id'],
            login: (string) $row['login'],
            hash: (string) $row['hash'],
        );
    }
}
