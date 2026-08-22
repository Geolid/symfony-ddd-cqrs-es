<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalApiKeyCredentialProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ApiKeyCredentialResult>
 *
 * @phpstan-type Row array{id: string, identity_id: string, label: string, key_id: string, secret_hash: string, issued_at: string, revoked: bool, revoked_at: string|null, identity_authenticatable: bool}
 */
final class DbalApiKeyCredentialFinder extends AbstractDbalFinder implements ApiKeyCredentialFinderInterface
{
    public function ofKeyId(string $keyId): ApiKeyCredentialResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('key_id = :keyId')
            ->setParameter('keyId', $keyId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw ApiKeyCredentialResultNotFoundException::forKeyId($keyId);
        }

        return $this->mapRow($row);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'label', 'key_id', 'secret_hash', 'issued_at', 'revoked', 'revoked_at', 'identity_authenticatable')
            ->from(DbalApiKeyCredentialProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): ApiKeyCredentialResult
    {
        return new ApiKeyCredentialResult(
            id: $row['id'],
            identityId: $row['identity_id'],
            label: $row['label'],
            keyId: $row['key_id'],
            secretHash: $row['secret_hash'],
            issuedAt: new \DateTimeImmutable($row['issued_at'], new \DateTimeZone('UTC')),
            revoked: (bool) $row['revoked'],
            revokedAt: null !== $row['revoked_at'] ? new \DateTimeImmutable($row['revoked_at'], new \DateTimeZone('UTC')) : null,
            identityAuthenticatable: (bool) $row['identity_authenticatable'],
        );
    }
}
