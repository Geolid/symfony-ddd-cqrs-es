<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Doubles;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

final readonly class StubPasswordPolicy implements PasswordPolicyInterface
{
    public function __construct(
        private bool $strongEnough = true,
        private bool $compromised = false,
    ) {
    }

    public function isStrongEnough(#[\SensitiveParameter] Password $password): bool
    {
        return $this->strongEnough;
    }

    public function isCompromised(#[\SensitiveParameter] Password $password): bool
    {
        return $this->compromised;
    }
}
