<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityActivated;
use Iam\Identity\Domain\Event\IdentityEmailConfirmationResendRequested;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityErasureCancelled;
use Iam\Identity\Domain\Event\IdentityErasureRequested;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Exception\EmailConfirmationResendRequestedTooRecentlyException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotPendingException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
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
use Shared\Domain\Service\VerificationCodeVerifierInterface;
use Shared\Tests\Support\Double\StubVerificationCodeVerifier;

final class IdentityTest extends AggregateRootTestCase
{
    private IdentityId $id;
    private FullName $fullName;
    private Email $email;
    private Reason $reason;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $activatedAt;
    private string $confirmationCode;
    private VerificationCodeVerifierInterface $verifier;
    private \DateTimeImmutable $suspendedAt;
    private \DateTimeImmutable $reactivatedAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;
    private \DateTimeImmutable $emailConfirmationResendRequestedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = IdentityId::fromString(Uuid::uuid7()->toString());
        $this->fullName = IdentityBuilder::sample('fullName');
        $this->email = IdentityBuilder::sample('email');
        $this->reason = IdentityBuilder::sample('reason');
        $this->registeredAt = IdentityBuilder::sample('registeredAt');
        $this->activatedAt = IdentityBuilder::sample('activatedAt');
        $this->confirmationCode = IdentityBuilder::sample('confirmationCode');
        $this->verifier = new StubVerificationCodeVerifier();
        $this->suspendedAt = IdentityBuilder::sample('suspendedAt');
        $this->reactivatedAt = IdentityBuilder::sample('reactivatedAt');
        $this->requestedAt = IdentityBuilder::sample('requestedAt');
        $this->cancelledAt = IdentityBuilder::sample('cancelledAt');
        $this->erasedAt = IdentityBuilder::sample('erasedAt');
        $this->emailConfirmationResendRequestedAt = IdentityBuilder::sample('emailConfirmationResendRequestedAt');
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
    public function itActivates(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->activate($this->confirmationCode, $this->verifier, $this->activatedAt))
            ->then($this->activated());
    }

    #[Test]
    public function itDoesNotActivateWhenAlreadyActive(): void
    {
        $this
            ->given($this->registered(), $this->activated())
            ->when(fn (Identity $identity) => $identity->activate($this->confirmationCode, $this->verifier, IdentityBuilder::sample('activatedAt')))
            ->then();
    }

    #[Test]
    public function itCannotActivateWhenErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(fn (Identity $identity) => $identity->activate($this->confirmationCode, $this->verifier, $this->activatedAt))
            ->expectsException(IdentityAlreadyErasedException::class);
    }

    #[Test]
    public function itCannotActivateWhenSuspended(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->suspended(),
            )
            ->when(fn (Identity $identity) => $identity->activate($this->confirmationCode, $this->verifier, $this->activatedAt))
            ->expectsException(IdentityNotPendingException::class);
    }

    #[Test]
    public function itCannotActivateWithInvalidCode(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->activate($this->confirmationCode, new StubVerificationCodeVerifier(valid: false), $this->activatedAt))
            ->expectsException(InvalidConfirmationCodeException::class);
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
                $this->activated(),
            )
            ->when(fn (Identity $identity) => $identity->reactivate(IdentityBuilder::sample('reason'), $this->reactivatedAt))
            ->then();
    }

    #[Test]
    public function itCannotReactivateWhenPending(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->reactivate($this->reason, $this->reactivatedAt))
            ->expectsException(IdentityNotSuspendedException::class);
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
    public function itRequestsEmailConfirmationResend(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->requestEmailConfirmationResend($this->emailConfirmationResendRequestedAt))
            ->then($this->emailConfirmationResendRequested());
    }

    #[Test]
    public function itCannotRequestEmailConfirmationResendWhenAlreadyActive(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->activated(),
            )
            ->when(fn (Identity $identity) => $identity->requestEmailConfirmationResend($this->emailConfirmationResendRequestedAt))
            ->expectsException(IdentityNotPendingException::class);
    }

    #[Test]
    public function itCannotRequestEmailConfirmationResendTooSoon(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->emailConfirmationResendRequested(),
            )
            ->when(fn (Identity $identity) => $identity->requestEmailConfirmationResend($this->emailConfirmationResendRequestedAt->modify('+1 second')))
            ->expectsException(EmailConfirmationResendRequestedTooRecentlyException::class);
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
    public function itErasesUnconfirmed(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->eraseUnconfirmed($this->erasedAt))
            ->then(new IdentityErased($this->id, $this->erasedAt));
    }

    #[Test]
    public function itDoesNotEraseUnconfirmedWhenActive(): void
    {
        $this
            ->given($this->registered(), $this->activated())
            ->when(static fn (Identity $identity) => $identity->eraseUnconfirmed(IdentityBuilder::sample('erasedAt')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseUnconfirmedWhenNotExpired(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Identity $identity) => $identity->eraseUnconfirmed($this->registeredAt->modify('+1 hour')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseUnconfirmedWhenAlreadyErased(): void
    {
        $this
            ->given(
                $this->registered(),
                $this->erasureRequested(),
                $this->erased(),
            )
            ->when(static fn (Identity $identity) => $identity->eraseUnconfirmed(IdentityBuilder::sample('erasedAt')))
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

    private function activated(): IdentityActivated
    {
        return new IdentityActivated($this->id, $this->activatedAt);
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

    private function emailConfirmationResendRequested(): IdentityEmailConfirmationResendRequested
    {
        return new IdentityEmailConfirmationResendRequested($this->id, $this->emailConfirmationResendRequestedAt);
    }
}
