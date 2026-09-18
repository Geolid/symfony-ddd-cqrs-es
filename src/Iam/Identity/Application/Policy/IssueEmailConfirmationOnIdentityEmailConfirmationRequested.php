<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Iam\Identity\Domain\Event\IdentityEmailConfirmationRequested;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Policy;
use Shared\Domain\Service\CodeChallengerInterface;

#[Policy('iam.identity.issue_email_confirmation_on_identity_email_confirmation_requested')]
final readonly class IssueEmailConfirmationOnIdentityEmailConfirmationRequested
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private CodeChallengerInterface $codeChallenger,
        private IdentityNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws MailerException
     */
    #[Subscribe(IdentityEmailConfirmationRequested::class)]
    public function __invoke(IdentityEmailConfirmationRequested $event): void
    {
        $identity = $this->identityFinder->ofId($event->id->toString());
        $code = $this->codeChallenger->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $event->id->toString(), $this->clock->now());
        $this->notifier->notifyEmailConfirmationCode($identity->email, $code);
    }
}
