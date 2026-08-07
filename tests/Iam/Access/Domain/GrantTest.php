<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Domain;

use Iam\Access\Domain\Event\PermissionGranted;
use Iam\Access\Domain\Event\PermissionRevoked;
use Iam\Access\Domain\Exception\PermissionAlreadyRevokedException;
use Iam\Access\Domain\Grant;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Access\Domain\ValueObject\Permission;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class GrantTest extends AggregateRootTestCase
{
    #[Test]
    public function itGrantsAPermission(): void
    {
        $id = GrantId::generate();
        $identityId = 'an-identity-id';
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Grant::grant($id, $identityId, Permission::fromString('sales:order_write'), $grantedAt))
            ->then(new PermissionGranted($id->toString(), $identityId, 'sales:order_write', $grantedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itRevokesAPermission(): void
    {
        $id = GrantId::generate()->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new PermissionGranted($id, 'an-identity-id', 'sales:order_write', $grantedAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Grant $grant) => $grant->revoke($revokedAt))
            ->then(new PermissionRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotRevokeAnAlreadyRevokedPermission(): void
    {
        $id = GrantId::generate()->toString();
        $grantedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $revokedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new PermissionGranted($id, 'an-identity-id', 'sales:order_write', $grantedAt->format(\DateTimeInterface::ATOM)),
                new PermissionRevoked($id, $revokedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Grant $grant) => $grant->revoke(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(PermissionAlreadyRevokedException::class);
    }

    protected function aggregateClass(): string
    {
        return Grant::class;
    }
}
