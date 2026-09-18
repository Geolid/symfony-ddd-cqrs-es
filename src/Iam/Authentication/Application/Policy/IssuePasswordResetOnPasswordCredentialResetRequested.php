<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\Notification\AuthenticationNotifierInterface;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialResetRequested;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialVerificationCodePurpose;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Psr\Clock\ClockInterface;
use Shared\Application\Policy;
use Shared\Domain\Service\VerificationCodeInterface;

#[Policy('iam.authentication.issue_password_reset_on_password_credential_reset_requested')]
final readonly class IssuePasswordResetOnPasswordCredentialResetRequested
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private VerificationCodeInterface $verifier,
        private AuthenticationNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     */
    #[Subscribe(PasswordCredentialResetRequested::class)]
    public function __invoke(PasswordCredentialResetRequested $event): void
    {
        $identity = $this->identityFinder->ofId($event->identityId);
        $code = $this->verifier->issue(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $event->identityId, $this->clock->now());
        $this->notifier->notifyPasswordResetCode($identity->email, $code);
    }
}
