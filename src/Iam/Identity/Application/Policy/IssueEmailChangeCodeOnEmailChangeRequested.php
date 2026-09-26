<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Iam\Identity\Domain\Event\IdentityEmailChangeRequested;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Policy;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;

#[Policy('iam.identity.issue_email_change_code_on_email_change_requested')]
final readonly class IssueEmailChangeCodeOnEmailChangeRequested
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
    #[Subscribe(IdentityEmailChangeRequested::class)]
    public function __invoke(IdentityEmailChangeRequested $event): void
    {
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CHANGE, $event->id->toString()), $this->clock->now());
        $this->notifier->notifyEmailChangeCode($event->email->value, $code);
    }
}
