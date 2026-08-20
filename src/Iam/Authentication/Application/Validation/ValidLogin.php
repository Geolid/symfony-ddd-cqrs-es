<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Validation;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidLogin extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: 50),
            new ValidValueObject(Login::class, method: 'fromString'),
        ];
    }
}
