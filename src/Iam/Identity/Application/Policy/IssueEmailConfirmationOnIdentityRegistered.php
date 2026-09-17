<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Application\IdentityVerificationCodePurpose;
use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Policy;
use Shared\Application\VerificationCode\VerificationCode;

#[Policy('iam.identity.issue_email_confirmation_on_identity_registered')]
final readonly class IssueEmailConfirmationOnIdentityRegistered
{
    public function __construct(
        private VerificationCode $verificationCode,
        private IdentityNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    #[Subscribe(IdentityRegistered::class)]
    public function __invoke(IdentityRegistered $event): void
    {
        $code = $this->verificationCode->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $event->id->toString(), $this->clock->now());
        $this->notifier->notifyEmailConfirmationCode($event->email->value, $code);
    }
}
