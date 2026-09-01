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
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class IdentityTest extends AggregateRootTestCase
{
    private IdentityId $id;
    private Reason $reason;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $suspendedAt;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = IdentityTestFactory::sample('id');
        $this->reason = IdentityTestFactory::sample('reason');
        $this->registeredAt = IdentityTestFactory::sample('registeredAt');
        $this->suspendedAt = IdentityTestFactory::sample('suspendedAt');
        $this->erasedAt = IdentityTestFactory::sample('erasedAt');
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
            ->when(fn (Identity $identity) => $identity->suspend(IdentityTestFactory::sample('reason'), $this->suspendedAt))
            ->then();
    }

    #[Test]
    public function itCannotSuspendWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->suspend($this->reason, $this->suspendedAt))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itReactivatesWhenSuspended(): void
    {
        $reason = IdentityTestFactory::sample('reason');
        $reactivatedAt = IdentityTestFactory::sample('reactivatedAt');

        $this
            ->given(
                $this->registered(),
                $this->suspended(),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate($reason, $reactivatedAt))
            ->then(new IdentityReactivated($this->id->toString(), $reason->value, $reactivatedAt));
    }

    #[Test]
    public function itDoesNotReactivateWhenNotSuspended(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Identity $identity) => $identity->reactivate(IdentityTestFactory::sample('reason'), IdentityTestFactory::sample('reactivatedAt')))
            ->then();
    }

    #[Test]
    public function itCannotReactivateWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->suspended(),
                $this->erased(),
            )
            ->when(static fn (Identity $identity) => $identity->reactivate(IdentityTestFactory::sample('reason'), IdentityTestFactory::sample('reactivatedAt')))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itErases(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->erase($this->erasedAt))
            ->then($this->erased());
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->erase($this->erasedAt))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }

    private function registered(): IdentityRegistered
    {
        return new IdentityRegistered($this->id->toString(), $this->registeredAt);
    }

    private function suspended(): IdentitySuspended
    {
        return new IdentitySuspended($this->id->toString(), $this->reason->value, $this->suspendedAt);
    }

    private function erased(): IdentityErased
    {
        return new IdentityErased($this->id->toString(), $this->erasedAt);
    }
}
