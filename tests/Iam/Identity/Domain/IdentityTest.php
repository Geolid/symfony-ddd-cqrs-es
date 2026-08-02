<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadySuspendedException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\IdentityId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class IdentityTest extends AggregateRootTestCase
{
    #[Test]
    public function itRegistersAnIdentity(): void
    {
        $id = IdentityId::generate();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Identity::register($id, $registeredAt))
            ->then(new IdentityRegistered($id->toString(), $registeredAt->format('c')));
    }

    #[Test]
    public function itSuspendsAnIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format('c')))
            ->when(static fn (Identity $identity) => $identity->suspend($suspendedAt))
            ->then(new IdentitySuspended($id, $suspendedAt->format('c')));
    }

    #[Test]
    public function itCannotSuspendAnAlreadySuspendedIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format('c')),
                new IdentitySuspended($id, $suspendedAt->format('c')),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(IdentityAlreadySuspendedException::class);
    }

    #[Test]
    public function itReactivatesASuspendedIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $reactivatedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format('c')),
                new IdentitySuspended($id, $suspendedAt->format('c')),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate($reactivatedAt))
            ->then(new IdentityReactivated($id, $reactivatedAt->format('c')));
    }

    #[Test]
    public function itCannotReactivateAnIdentityThatIsNotSuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format('c')))
            ->when(static fn (Identity $identity) => $identity->reactivate(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(IdentityNotSuspendedException::class);
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }
}
