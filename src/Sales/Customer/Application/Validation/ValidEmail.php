<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Validation;

use Sales\Customer\Domain\ValueObject\Email;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Email(),
            new ValidValueObject(Email::class, method: 'fromString'),
        ];
    }
}
