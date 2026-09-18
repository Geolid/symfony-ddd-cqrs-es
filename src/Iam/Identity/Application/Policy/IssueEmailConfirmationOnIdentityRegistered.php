<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Policy;

use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Policy;
use Shared\Domain\Service\VerificationCodeInterface;

#[Policy('iam.identity.issue_email_confirmation_on_identity_registered')]
final readonly class IssueEmailConfirmationOnIdentityRegistered
{
    public function __construct(
        private VerificationCodeInterface $verifier,
        private IdentityNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    #[Subscribe(IdentityRegistered::class)]
    public function __invoke(IdentityRegistered $event): void
    {
        $code = $this->verifier->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $event->id->toString(), $this->clock->now());
        $this->notifier->notifyEmailConfirmationCode($event->email->value, $code);
    }
}
