<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Validation;

use Iam\Identity\Domain\ValueObject\Reason;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidReason extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: 255),
            new ValidValueObject(Reason::class, method: 'fromString'),
        ];
    }
}
