<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class IdentityTest extends AggregateRootTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        $id = IdentityId::generate();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Identity => Identity::register($id, $now))
            ->then(new IdentityRegistered($id->toString(), $now));
    }

    #[Test]
    public function itSuspends(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = $now->modify('+1 day');

        $this
            ->given(new IdentityRegistered($id, $now))
            ->when(static fn (Identity $identity) => $identity->suspend(Reason::fromString('Suspected fraudulent activity'), $suspendedAt))
            ->then(new IdentitySuspended($id, 'Suspected fraudulent activity', $suspendedAt));
    }

    #[Test]
    public function itDoesNotSuspendWhenAlreadySuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $now),
                new IdentitySuspended($id, 'Suspected fraudulent activity', $now->modify('+1 day')),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(Reason::fromString('Manual request'), $now->modify('+2 days')))
            ->then();
    }

    #[Test]
    public function itCannotSuspendWhenErased(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $now),
                new IdentityErased($id, $now->modify('+1 day')),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(Reason::fromString('Suspected fraudulent activity'), $now->modify('+2 days')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itReactivatesWhenSuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $reactivatedAt = $now->modify('+2 days');

        $this
            ->given(
                new IdentityRegistered($id, $now),
                new IdentitySuspended($id, 'Suspected fraudulent activity', $now->modify('+1 day')),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(Reason::fromString('Appeal upheld'), $reactivatedAt))
            ->then(new IdentityReactivated($id, 'Appeal upheld', $reactivatedAt));
    }

    #[Test]
    public function itDoesNotReactivateWhenNotSuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $now))
            ->when(static fn (Identity $identity) => $identity->reactivate(Reason::fromString('Appeal upheld'), $now->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itCannotReactivateWhenErased(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $now),
                new IdentitySuspended($id, 'Suspected fraudulent activity', $now->modify('+1 day')),
                new IdentityErased($id, $now->modify('+2 days')),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(Reason::fromString('Appeal upheld'), $now->modify('+3 days')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itErases(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = $now->modify('+1 day');

        $this
            ->given(new IdentityRegistered($id, $now))
            ->when(static fn (Identity $identity) => $identity->erase($erasedAt))
            ->then(new IdentityErased($id, $erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $id = IdentityId::generate()->toString();
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $now),
                new IdentityErased($id, $now->modify('+1 day')),
            )
            ->when(static fn (Identity $identity) => $identity->erase($now->modify('+2 days')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }
}
