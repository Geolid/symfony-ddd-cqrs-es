<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Validation;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidPassword extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Length(min: Password::MIN_LENGTH, max: Password::MAX_LENGTH),
            new PasswordStrength(minScore: PasswordStrengthInterface::MIN_REQUIRED_SCORE),
            new NotCompromisedPassword(skipOnError: true),
            new ValidValueObject(Password::class, method: 'fromString'),
        ];
    }
}
