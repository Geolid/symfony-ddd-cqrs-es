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
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn (): Identity => Identity::register($id, $registeredAt))
            ->then(new IdentityRegistered($id->toString(), $registeredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itSuspends(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Identity $identity) => $identity->suspend(Reason::fromString('Suspected fraudulent activity'), $suspendedAt))
            ->then(new IdentitySuspended($id, 'Suspected fraudulent activity', $suspendedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotSuspendWhenAlreadySuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentitySuspended($id, 'Suspected fraudulent activity', $suspendedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(Reason::fromString('Manual request'), new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCannotSuspendWhenErased(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentityErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itReactivatesWhenSuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $reactivatedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentitySuspended($id, 'Suspected fraudulent activity', $suspendedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(Reason::fromString('Appeal upheld'), $reactivatedAt))
            ->then(new IdentityReactivated($id, 'Appeal upheld', $reactivatedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotReactivateWhenNotSuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Identity $identity) => $identity->reactivate(Reason::fromString('Appeal upheld'), new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->then();
    }

    #[Test]
    public function itCannotReactivateWhenErased(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentitySuspended($id, 'Suspected fraudulent activity', $suspendedAt->format(\DateTimeInterface::ATOM)),
                new IdentityErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(Reason::fromString('Appeal upheld'), new \DateTimeImmutable('2026-01-04T00:00:00+00:00')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itErases(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Identity $identity) => $identity->erase($erasedAt))
            ->then(new IdentityErased($id, $erasedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentityErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->erase(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }
}
