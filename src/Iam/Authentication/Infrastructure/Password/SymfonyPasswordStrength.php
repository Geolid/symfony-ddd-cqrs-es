<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Password;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Symfony\Component\Validator\Constraints\PasswordStrength as PasswordStrengthConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SymfonyPasswordStrength implements PasswordStrengthInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function isSufficient(#[\SensitiveParameter] Password $password): bool
    {
        $violations = $this->validator->validate($password->value, new PasswordStrengthConstraint(minScore: PasswordStrengthInterface::MIN_REQUIRED_SCORE));

        return 0 === $violations->count();
    }
}
