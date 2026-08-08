<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Domain;

use Iam\Access\Domain\Event\PermissionGranted;
use Iam\Access\Domain\Event\PermissionReactivated;
use Iam\Access\Domain\Event\PermissionRevoked;
use Iam\Access\Domain\Exception\PermissionAlreadyRevokedException;
use Iam\Access\Domain\Exception\PermissionNotRevokedException;
use Iam\Access\Domain\Grant;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Access\Domain\ValueObject\Permission;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class GrantTest extends AggregateRootTestCase
{
    #[Test]
    public function itGrantsAPermission(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = GrantId::forIdentityAndPermission($identityId, 'fixture:write');
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Grant::grant($id, $identityId, Permission::fromString('fixture:write'), $grantedAt))
            ->then(new PermissionGranted($id->toString(), $identityId, 'fixture:write', $grantedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itRevokesAPermission(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = GrantId::forIdentityAndPermission($identityId, 'fixture:write')->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PermissionGranted($id, $identityId, 'fixture:write', $grantedAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Grant $grant) => $grant->revoke($revokedAt))
            ->then(new PermissionRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotRevokeAnAlreadyRevokedPermission(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = GrantId::forIdentityAndPermission($identityId, 'fixture:write')->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new PermissionGranted($id, $identityId, 'fixture:write', $grantedAt->format(\DateTimeInterface::ATOM)),
                new PermissionRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Grant $grant) => $grant->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(PermissionAlreadyRevokedException::class);
    }

    #[Test]
    public function itReactivatesARevokedPermission(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = GrantId::forIdentityAndPermission($identityId, 'fixture:write')->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $reactivatedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                new PermissionGranted($id, $identityId, 'fixture:write', $grantedAt->format(\DateTimeInterface::ATOM)),
                new PermissionRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Grant $grant) => $grant->reactivate($reactivatedAt))
            ->then(new PermissionReactivated($id, $reactivatedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCanBeRevokedAgainAfterBeingReactivated(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = GrantId::forIdentityAndPermission($identityId, 'fixture:write')->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $reactivatedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');
        $revokedAgainAt = new \DateTimeImmutable('2026-01-04T00:00:00+00:00');

        $this
            ->given(
                new PermissionGranted($id, $identityId, 'fixture:write', $grantedAt->format(\DateTimeInterface::ATOM)),
                new PermissionRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
                new PermissionReactivated($id, $reactivatedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Grant $grant) => $grant->revoke($revokedAgainAt))
            ->then(new PermissionRevoked($id, $revokedAgainAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotReactivateAnActivePermission(): void
    {
        $identityId = Uuid::uuid7()->toString();
        $id = GrantId::forIdentityAndPermission($identityId, 'fixture:write')->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new PermissionGranted($id, $identityId, 'fixture:write', $grantedAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Grant $grant) => $grant->reactivate(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(PermissionNotRevokedException::class);
    }

    protected function aggregateClass(): string
    {
        return Grant::class;
    }
}
