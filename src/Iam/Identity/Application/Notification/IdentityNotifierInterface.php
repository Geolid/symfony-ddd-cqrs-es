<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Notification;

use Shared\Application\Mailer\Exception\MailerException;

interface IdentityNotifierInterface
{
    /**
     * @throws MailerException
     */
    public function notifyEmailConfirmationCode(string $email, #[\SensitiveParameter] string $code): void;
}
