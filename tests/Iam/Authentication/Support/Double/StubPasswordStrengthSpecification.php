<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Double;

use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

final readonly class StubPasswordStrengthSpecification implements PasswordStrengthSpecificationInterface
{
    public function __construct(private bool $sufficient = true)
    {
    }

    public function isSatisfiedBy(#[\SensitiveParameter] Password $password): bool
    {
        return $this->sufficient;
    }
}
