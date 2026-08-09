<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadySuspendedException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
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
            ->then(new IdentityRegistered($id->toString(), $registeredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itSuspendsAnIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Identity $identity) => $identity->suspend($suspendedAt))
            ->then(new IdentitySuspended($id, $suspendedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotSuspendAnAlreadySuspendedIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentitySuspended($id, $suspendedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(IdentityAlreadySuspendedException::class);
    }

    #[Test]
    public function itCannotSuspendAnErasedIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentityErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->suspend(new \DateTimeImmutable('2026-01-03T00:00:00+00:00')))
            ->expectsException(IdentityAlreadyErasedException::class);
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
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentitySuspended($id, $suspendedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate($reactivatedAt))
            ->then(new IdentityReactivated($id, $reactivatedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotReactivateAnIdentityThatIsNotSuspended(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Identity $identity) => $identity->reactivate(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(IdentityNotSuspendedException::class);
    }

    #[Test]
    public function itCannotReactivateAnErasedIdentity(): void
    {
        $id = IdentityId::generate()->toString();
        $registeredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $suspendedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $erasedAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                new IdentityRegistered($id, $registeredAt->format(\DateTimeInterface::ATOM)),
                new IdentitySuspended($id, $suspendedAt->format(\DateTimeInterface::ATOM)),
                new IdentityErased($id, $erasedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(new \DateTimeImmutable('2026-01-04T00:00:00+00:00')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itErasesAnIdentity(): void
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
    public function itDoesNotEraseAnAlreadyErasedIdentity(): void
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
