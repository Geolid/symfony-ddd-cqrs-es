<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Notification;

use Shared\Application\Mailer\Exception\MailerException;

interface AuthenticationNotifierInterface
{
    /**
     * @throws MailerException
     */
    public function notifyPasswordResetCode(string $email, #[\SensitiveParameter] string $code): void;
}
