<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Service;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

interface PasswordStrengthInterface
{
    public function isSufficient(#[\SensitiveParameter] Password $password): bool;
}
