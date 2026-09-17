<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Notification;

interface AuthenticationNotifierInterface
{
    public function notifyPasswordResetCode(string $email, #[\SensitiveParameter] string $code): void;
}
