<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Notification;

use Iam\Identity\Application\Notification\IdentityNotifierInterface;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Mailer\MailerInterface;

final readonly class MailerIdentityNotifier implements IdentityNotifierInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * @throws MailerException
     */
    public function notifyEmailConfirmationCode(string $email, #[\SensitiveParameter] string $code): void
    {
        $this->mailer->send($email, 'Confirm your email address', \sprintf('Your confirmation code is: %s', $code));
    }

    public function notifyEmailChangeCode(string $email, #[\SensitiveParameter] string $code): void
    {
        $this->mailer->send($email, 'Confirm your new email address', \sprintf('Your verification code is: %s', $code));
    }

    /**
     * @throws MailerException
     */
    public function notifyErasureRequested(string $email, \DateTimeImmutable $effectiveAt): void
    {
        $this->mailer->send($email, 'Your account deletion request', \sprintf('We received your request to delete your account. It will be deleted on %s unless you cancel it from your account settings.', $effectiveAt->format('Y-m-d')));
    }
}
