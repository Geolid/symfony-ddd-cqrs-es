<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Symfony\Component\Validator\Constraints\PasswordStrength as PasswordStrengthConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class PasswordStrength implements PasswordStrengthInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function isSufficient(#[\SensitiveParameter] Password $password): bool
    {
        $violations = $this->validator->validate($password->value, new PasswordStrengthConstraint(minScore: PasswordStrengthConstraint::STRENGTH_STRONG));

        return 0 === $violations->count();
    }
}
