<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Service;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

interface PasswordPolicyInterface
{
    public function isStrongEnough(#[\SensitiveParameter] Password $password): bool;

    public function isCompromised(#[\SensitiveParameter] Password $password): bool;
}
