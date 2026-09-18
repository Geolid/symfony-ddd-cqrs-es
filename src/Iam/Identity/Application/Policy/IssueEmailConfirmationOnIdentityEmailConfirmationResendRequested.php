<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Iam\Identity\Domain\Event\IdentityEmailConfirmationResendRequested;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Policy;
use Shared\Domain\Service\VerificationCodeInterface;

#[Policy('iam.identity.issue_email_confirmation_on_identity_email_confirmation_resend_requested')]
final readonly class IssueEmailConfirmationOnIdentityEmailConfirmationResendRequested
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private VerificationCodeInterface $verifier,
        private IdentityNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     */
    #[Subscribe(IdentityEmailConfirmationResendRequested::class)]
    public function __invoke(IdentityEmailConfirmationResendRequested $event): void
    {
        $identity = $this->identityFinder->ofId($event->id->toString());
        $code = $this->verifier->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $event->id->toString(), $this->clock->now());
        $this->notifier->notifyEmailConfirmationCode($identity->email, $code);
    }
}
