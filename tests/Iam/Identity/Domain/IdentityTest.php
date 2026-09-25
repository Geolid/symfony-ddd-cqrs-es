<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityConfirmationRequested;
use Iam\Identity\Domain\Event\IdentityConfirmed;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\ConfirmationRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyConfirmedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Tests\Support\Double\FakeCodeChallenger;

final class IdentityTest extends AggregateRootTestCase
{
    private IdentityId $id;
    private FullName $fullName;
    private Email $email;
    private Reason $reason;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $confirmedAt;
    private string $confirmationCode;
    private CodeChallengerInterface $codeChallenger;
    private \DateTimeImmutable $suspendedAt;
    private \DateTimeImmutable $reactivatedAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;
    private \DateTimeImmutable $confirmationRequestedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = IdentityId::fromString(Uuid::uuid7()->toString());
        $this->fullName = IdentityBuilder::sample('fullName');
        $this->email = IdentityBuilder::sample('email');
        $this->reason = IdentityBuilder::sample('reason');
        $this->registeredAt = IdentityBuilder::sample('registeredAt');
        $this->confirmedAt = IdentityBuilder::sample('confirmedAt');
        $this->confirmationCode = IdentityBuilder::sample('confirmationCode');
        $this->codeChallenger = new FakeCodeChallenger();
        $this->suspendedAt = IdentityBuilder::sample('suspendedAt');
        $this->reactivatedAt = IdentityBuilder::sample('reactivatedAt');
        $this->requestedAt = IdentityBuilder::sample('requestedAt');
        $this->cancelledAt = IdentityBuilder::sample('cancelledAt');
        $this->erasedAt = IdentityBuilder::sample('erasedAt');
        $this->confirmationRequestedAt = IdentityBuilder::sample('confirmationRequestedAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Identity => Identity::register($this->id, $this->fullName, $this->email, $this->registeredAt))
            ->then($this->registered());
    }

    #[Test]
    public function itConfirms(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->confirm($this->confirmationCode, $this->codeChallenger, $this->confirmedAt))
            ->then($this->confirmed());
    }

    #[Test]
    public function itConfirmsWhenSuspended(): void
    {
        $this
            ->given($this->registered(), $this->suspended())
            ->when(fn (Identity $identity) => $identity->confirm($this->confirmationCode, $this->codeChallenger, $this->confirmedAt))
            ->then($this->confirmed());
    }

    #[Test]
    public function itDoesNotConfirmWhenAlreadyConfirmed(): void
    {
        $this
            ->given($this->registered(), $this->confirmed())
            ->when(fn (Identity $identity) => $identity->confirm($this->confirmationCode, $this->codeChallenger, IdentityBuilder::sample('confirmedAt')))
            ->then();
    }

    #[Test]
    public function itCannotConfirmWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->confirm($this->confirmationCode, $this->codeChallenger, $this->confirmedAt))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itCannotConfirmWithInvalidCode(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->confirm('wrong', new FakeCodeChallenger(), $this->confirmedAt))
            ->expectsException(InvalidConfirmationCodeException::class);
    }

    #[Test]
    public function itSuspends(): void
    {
        $this
            ->given($this->registered(), $this->confirmed())
            ->when(fn (Identity $identity) => $identity->suspend($this->reason, $this->suspendedAt))
            ->then($this->suspended());
    }

    #[Test]
    public function itSuspendsWhenPending(): void
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
                $this->confirmed(),
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
                $this->confirmed(),
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->suspend($this->reason, $this->suspendedAt))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itReactivates(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->suspended(),
            )
            ->when(fn (Identity $identity) => $identity->reactivate($this->reason, $this->reactivatedAt))
            ->then(new IdentityReactivated($this->id, $this->reason, $this->reactivatedAt));
    }

    #[Test]
    public function itDoesNotReactivateWhenAlreadyActive(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->confirmed(),
            )
            ->when(fn (Identity $identity) => $identity->reactivate(IdentityBuilder::sample('reason'), $this->reactivatedAt))
            ->then();
    }

    #[Test]
    public function itCannotReactivateWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->suspended(),
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->reactivate($this->reason, $this->reactivatedAt))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itRequestsConfirmation(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->requestConfirmation($this->confirmationRequestedAt))
            ->then($this->confirmationRequested());
    }

    #[Test]
    public function itCannotRequestConfirmationWhenAlreadyConfirmed(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->confirmed(),
            )
            ->when(fn (Identity $identity) => $identity->requestConfirmation($this->confirmationRequestedAt))
            ->expectsException(IdentityAlreadyConfirmedException::class);
    }

    #[Test]
    public function itCannotRequestConfirmationTooSoon(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->confirmationRequested(),
            )
            ->when(fn (Identity $identity) => $identity->requestConfirmation($this->confirmationRequestedAt->modify('+1 second')))
            ->expectsException(ConfirmationRequestedTooRecentlyException::class)
            ->expectsExceptionMessage('requested too recently');
    }

    #[Test]
    public function itCannotRequestConfirmationTooSoonAfterRegistering(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->requestConfirmation($this->registeredAt->modify('+1 second')))
            ->expectsException(ConfirmationRequestedTooRecentlyException::class);
    }

    #[Test]
    public function itRequestsErasure(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->requestErasure($this->requestedAt))
            ->then($this->erasureRequested());
    }

    #[Test]
    public function itDoesNotRequestErasureWhenAlreadyRequested(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested())
            ->when(static fn (Identity $identity) => $identity->requestErasure(IdentityBuilder::sample('requestedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsErasure(): void
    {
        $this
            ->given($this->registered(), $this->erasureRequested())
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
            ->given($this->registered(), $this->erasureRequested())
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
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(static fn (Identity $identity) => $identity->erase(IdentityBuilder::sample('erasedAt')))
            ->then();
    }

    #[Test]
    public function itErasesPending(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->erasePending($this->erasedAt))
            ->then(new IdentityErased($this->id, $this->erasedAt));
    }

    #[Test]
    public function itDoesNotErasePendingWhenConfirmed(): void
    {
        $this
            ->given($this->registered(), $this->confirmed())
            ->when(static fn (Identity $identity) => $identity->erasePending(IdentityBuilder::sample('erasedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotErasePendingWhenNotExpired(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->erasePending($this->registeredAt->modify('+1 hour')))
            ->then();
    }

    #[Test]
    public function itDoesNotErasePendingWhenAlreadyErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(static fn (Identity $identity) => $identity->erasePending(IdentityBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Identity::class;
    }

    private function registered(): IdentityRegistered
    {
        return new IdentityRegistered($this->id, $this->fullName, $this->email, $this->registeredAt);
    }

    private function confirmed(): IdentityConfirmed
    {
        return new IdentityConfirmed($this->id, $this->confirmedAt);
    }

    private function suspended(): IdentitySuspended
    {
        return new IdentitySuspended($this->id, $this->reason, $this->suspendedAt);
    }

    private function erasureRequested(): IdentityErasureRequested
    {
        return new IdentityErasureRequested($this->id, $this->requestedAt);
    }

    private function erased(): IdentityErased
    {
        return new IdentityErased($this->id, $this->erasedAt);
    }

    private function confirmationRequested(): IdentityConfirmationRequested
    {
        return new IdentityConfirmationRequested($this->id, $this->confirmationRequestedAt);
    }
}
