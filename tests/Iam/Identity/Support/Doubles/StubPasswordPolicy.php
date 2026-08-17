<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Doubles;

use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\ValueObject\Password;

final class StubPasswordPolicy implements PasswordPolicyInterface
{
    public function __construct(
        private readonly bool $strongEnough = true,
        private readonly bool $compromised = false,
    ) {
    }

    public function isStrongEnough(Password $password): bool
    {
        return $this->strongEnough;
    }

    public function isCompromised(Password $password): bool
    {
        return $this->compromised;
    }
}
