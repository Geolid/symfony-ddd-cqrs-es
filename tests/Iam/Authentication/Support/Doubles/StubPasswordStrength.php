<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Doubles;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

final readonly class StubPasswordStrength implements PasswordStrengthInterface
{
    public function __construct(private bool $sufficient = true)
    {
    }

    public function isSufficient(#[\SensitiveParameter] Password $password): bool
    {
        return $this->sufficient;
    }
}
