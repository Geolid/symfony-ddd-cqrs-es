<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalApiKeyCredentialProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ApiKeyCredentialResult>
 */
final class DbalApiKeyCredentialFinder extends AbstractDbalFinder implements ApiKeyCredentialFinderInterface
{
    public function ofKeyId(string $keyId): ApiKeyCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($keyId): void {
                $qb->andWhere('key_id = :keyId')->setParameter('keyId', $keyId);
            },
        )->one() ?? throw ApiKeyCredentialResultNotFoundException::forKeyId($keyId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'label', 'key_id', 'secret_hash', 'issued_at', 'revoked', 'revoked_at', 'identity_authenticatable')
            ->from(DbalApiKeyCredentialProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ApiKeyCredentialResult::class;
    }
}
