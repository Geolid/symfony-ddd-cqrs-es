<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Notification;

use Iam\Authentication\Application\Notification\AuthenticationNotifierInterface;
use Shared\Application\Mailer\Exception\MailerException;
use Shared\Application\Mailer\MailerInterface;

final readonly class MailerAuthenticationNotifier implements AuthenticationNotifierInterface
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    /**
     * @throws MailerException
     */
    public function notifyPasswordResetCode(string $email, #[\SensitiveParameter] string $code): void
    {
        $this->mailer->send($email, 'Reset your password', \sprintf('Your password reset code is: %s', $code));
    }
}
