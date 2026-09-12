<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class IdentityTest extends AggregateRootTestCase
{
    private IdentityId $id;
    private Reason $reason;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $suspendedAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = IdentityId::fromString(Uuid::uuid7()->toString());
        $this->reason = IdentityBuilder::sample('reason');
        $this->registeredAt = IdentityBuilder::sample('registeredAt');
        $this->suspendedAt = IdentityBuilder::sample('suspendedAt');
        $this->requestedAt = IdentityBuilder::sample('requestedAt');
        $this->cancelledAt = IdentityBuilder::sample('cancelledAt');
        $this->erasedAt = IdentityBuilder::sample('erasedAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Identity => Identity::register($this->id, $this->registeredAt))
            ->then($this->registered());
    }

    #[Test]
    public function itSuspends(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->suspend($this->reason, $this->suspendedAt))
            ->then($this->suspended());
    }

    #[Test]
    public function itDoesNotSuspendWhenAlreadySuspended(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->suspended(),
            )
            ->when(fn (Identity $identity) => $identity->suspend(IdentityBuilder::sample('reason'), $this->suspendedAt))
            ->then();
    }

    #[Test]
    public function itCannotSuspendWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->requested(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->suspend($this->reason, $this->suspendedAt))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itReactivates(): void
    {
        $reason = IdentityBuilder::sample('reason');
        $reactivatedAt = IdentityBuilder::sample('reactivatedAt');

        $this
            ->given(
                $this->registered(),
                $this->suspended(),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate($reason, $reactivatedAt))
            ->then(new IdentityReactivated($this->id, $reason, $reactivatedAt));
    }

    #[Test]
    public function itDoesNotReactivateWhenNotSuspended(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Identity $identity) => $identity->reactivate(IdentityBuilder::sample('reason'), IdentityBuilder::sample('reactivatedAt')))
            ->then();
    }

    #[Test]
    public function itCannotReactivateWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->suspended(),
                $this->requested(),
                $this->erased(),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(IdentityBuilder::sample('reason'), IdentityBuilder::sample('reactivatedAt')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itRequestsErasure(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->requestErasure($this->requestedAt))
            ->then($this->requested());
    }

    #[Test]
    public function itDoesNotRequestErasureWhenAlreadyRequested(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(static fn (Identity $identity) => $identity->requestErasure(IdentityBuilder::sample('requestedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsErasure(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Identity $identity) => $identity->cancelErasure($this->cancelledAt))
            ->then(new IdentityErasureCancelled($this->id, $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelErasureWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Identity $identity) => $identity->cancelErasure(IdentityBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Identity $identity) => $identity->erase($this->erasedAt))
            ->then(new IdentityErased($this->id, $this->erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Identity $identity) => $identity->erase(IdentityBuilder::sample('erasedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->requested(),
                $this->erased(),
            )
            ->when(static fn (Identity $identity) => $identity->erase(IdentityBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }

    private function registered(): IdentityRegistered
    {
        return new IdentityRegistered($this->id, $this->registeredAt);
    }

    private function suspended(): IdentitySuspended
    {
        return new IdentitySuspended($this->id, $this->reason, $this->suspendedAt);
    }

    private function requested(): IdentityErasureRequested
    {
        return new IdentityErasureRequested($this->id, $this->requestedAt);
    }

    private function erased(): IdentityErased
    {
        return new IdentityErased($this->id, $this->erasedAt);
    }
}
