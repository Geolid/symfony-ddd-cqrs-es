<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Finder\Identity\IdentityResult;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<IdentityResult>
 */
final class DbalIdentityFinder extends AbstractDbalFinder implements IdentityFinderInterface
{
    public function ofId(string $identityId): IdentityResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($identityId): void {
                $qb->andWhere('identity_id = :identityId')->setParameter('identityId', $identityId);
            },
        )->one() ?? throw IdentityResultNotFoundException::forId($identityId);
    }

    public function ofEmail(string $email): IdentityResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($email): void {
                $qb->andWhere('email = :email')->setParameter('email', $email);
            },
        )->one() ?? throw IdentityResultNotFoundException::forEmail($email);
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('identity_id', 'full_name', 'email', 'verification_status', 'moderation_status')->from(DbalIdentityProjector::TABLE);
    }

    protected function resultClass(): string
    {
        return IdentityResult::class;
    }
}
