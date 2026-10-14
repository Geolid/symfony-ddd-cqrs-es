<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Infrastructure\Projection\Projector\DbalIdentityProjector;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractPaginatableDbalFinder;

/**
 * @extends AbstractPaginatableDbalFinder<IdentityResult>
 */
final class DbalIdentityFinder extends AbstractPaginatableDbalFinder implements IdentityFinderInterface
{
    public function ofId(string $id): IdentityResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw IdentityResultNotFoundException::forId($id);
    }

    public function ofEmailOrNull(string $email): ?IdentityResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($email): void {
                $qb->andWhere('email = :email')->setParameter('email', $email);
            },
        )->one();
    }

    public function pendingExpiredBefore(\DateTimeImmutable $now): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($now): void {
                $qb->andWhere('verification_status = :pending')
                    ->andWhere('pending_expires_at < :now')
                    ->setParameter('pending', IdentityVerificationStatus::PENDING)
                    ->setParameter('now', $now, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'full_name', 'email', 'verification_status', 'moderation_status', 'reason', 'registered_at', 'confirmation_requested_at', 'suspended_at', 'reactivated_at', 'erasure_status')
            ->from(DbalIdentityProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['registered_at' => SortDirection::Ascending, 'id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return IdentityResult::class;
    }
}
