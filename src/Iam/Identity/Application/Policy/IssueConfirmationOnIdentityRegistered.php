<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Policy;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;

#[Policy('iam.identity.issue_confirmation_on_identity_registered')]
final readonly class IssueConfirmationOnIdentityRegistered
{
    public function __construct(
        private CodeChallengerInterface $codeChallenger,
        private IdentityNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws MailerException
     */
    #[Subscribe(IdentityRegistered::class)]
    public function __invoke(IdentityRegistered $event): void
    {
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $event->id->toString()), $this->clock->now());
        $this->notifier->notifyEmailConfirmationCode($event->email->value, $code);
    }
}
