<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\ValueObject\Password;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class PasswordPolicy implements PasswordPolicyInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function isStrongEnough(#[\SensitiveParameter] Password $password): bool
    {
        $violations = $this->validator->validate($password->value, new PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG));

        return 0 === $violations->count();
    }

    public function isCompromised(#[\SensitiveParameter] Password $password): bool
    {
        $violations = $this->validator->validate($password->value, new NotCompromisedPassword(skipOnError: true));

        return $violations->count() > 0;
    }
}
