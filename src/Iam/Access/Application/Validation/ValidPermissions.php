<?php

declare(strict_types=1);

namespace Iam\Access\Application\Validation;

use Shared\Application\Language\PublishedLanguageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidPermissions extends Compound implements PublishedLanguageInterface
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Type('array'),
            new Assert\Count(min: 1),
            new Assert\All([new ValidPermission()]),
        ];
    }
}
