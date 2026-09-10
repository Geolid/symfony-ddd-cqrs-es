<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Validation;

use Shared\Application\Validation\ValidValueObject;
use Shopping\Checkout\Domain\ValueObject\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Type('string'),
                new Assert\Email(),
                new ValidValueObject(Email::class, method: 'fromString'),
            ]),
        ];
    }
}
