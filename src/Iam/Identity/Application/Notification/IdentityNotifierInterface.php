<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Notification;

interface IdentityNotifierInterface
{
    public function notifyEmailConfirmationCode(string $email, #[\SensitiveParameter] string $code): void;
}
