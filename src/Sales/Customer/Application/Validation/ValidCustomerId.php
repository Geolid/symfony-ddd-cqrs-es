<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Validation;

use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidCustomerId extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Uuid(strict: false),
            new ValidValueObject(CustomerId::class),
        ];
    }
}
