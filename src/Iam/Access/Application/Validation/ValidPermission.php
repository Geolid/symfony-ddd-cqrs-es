<?php

declare(strict_types=1);

namespace Iam\Access\Application\Validation;

use Iam\Access\Domain\ValueObject\Permission;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidPermission extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Regex(pattern: Permission::PATTERN, message: 'iam.access.permission.invalid_format'),
            new Assert\Length(max: 64),
            new ValidValueObject(Permission::class),
        ];
    }
}
