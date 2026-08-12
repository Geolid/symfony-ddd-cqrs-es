<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Validation;

use Catalog\Product\Domain\ValueObject\Label;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidLabel extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: 255),
            new ValidValueObject(Label::class, method: 'fromString'),
        ];
    }
}
