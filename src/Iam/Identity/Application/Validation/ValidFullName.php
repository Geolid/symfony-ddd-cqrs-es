<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Validation;

use Iam\Identity\Domain\ValueObject\FullName;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidFullName extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: FullName::MAX_LENGTH),
            new ValidValueObject(FullName::class, method: 'fromString'),
        ];
    }
}
