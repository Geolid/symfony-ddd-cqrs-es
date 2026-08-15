<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Validation;

use Iam\Identity\Domain\ValueObject\Password;
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
            new Assert\Length(min: 12, max: 4096),
            new PasswordStrength(minScore: PasswordStrength::STRENGTH_VERY_STRONG),
            new NotCompromisedPassword(skipOnError: true),
            new ValidValueObject(Password::class),
        ];
    }
}
