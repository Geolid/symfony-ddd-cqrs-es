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
}
