<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalTotpCredentialProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<TotpCredentialResult>
 */
final class DbalTotpCredentialFinder extends AbstractDbalFinder implements TotpCredentialFinderInterface
{
    public function ofId(string $id): TotpCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw TotpCredentialResultNotFoundException::forId($id);
    }

    public function ofIdentity(string $identityId): TotpCredentialResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId')
                    ->andWhere('confirmed = :confirmed')
                    ->andWhere('revoked = :revoked')
                    ->setParameter('identityId', $identityId)
                    ->setParameter('confirmed', true)
                    ->setParameter('revoked', false);
            },
        )->one() ?? throw TotpCredentialResultNotFoundException::forIdentity($identityId);
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'identity_id', 'encrypted_secret', 'enrolled_at', 'confirmed', 'confirmed_at', 'revoked', 'revoked_at', 'identity_authenticatable')
            ->from(DbalTotpCredentialProjector::TABLE);
    }

    protected function resultClass(): string
    {
        return TotpCredentialResult::class;
    }
}
