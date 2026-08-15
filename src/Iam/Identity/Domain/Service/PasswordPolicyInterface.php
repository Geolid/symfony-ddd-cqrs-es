<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Service;

use Iam\Identity\Domain\ValueObject\Password;

interface PasswordPolicyInterface
{
    public function isStrongEnough(#[\SensitiveParameter] Password $password): bool;

    public function isCompromised(#[\SensitiveParameter] Password $password): bool;
}
