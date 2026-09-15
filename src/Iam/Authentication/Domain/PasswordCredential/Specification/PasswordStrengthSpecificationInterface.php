<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Specification;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;

interface PasswordStrengthSpecificationInterface
{
    public const int SCORE_WEAK = 1;
    public const int SCORE_MEDIUM = 2;
    public const int SCORE_STRONG = 3;
    public const int SCORE_VERY_STRONG = 4;

    public const int MIN_REQUIRED_SCORE = self::SCORE_MEDIUM;

    public function isSatisfiedBy(#[\SensitiveParameter] Password $password): bool;
}
