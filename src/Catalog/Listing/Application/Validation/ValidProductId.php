<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Validation;

use Catalog\Listing\Domain\ValueObject\ProductId;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidProductId extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotBlank(),
                new Assert\Type('string'),
                new Assert\Uuid(),
                new ValidValueObject(ProductId::class, method: 'fromString'),
            ]),
        ];
    }
}
