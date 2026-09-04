<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Validation;

use Catalog\Listing\Domain\ValueObject\ProductUniqueKey;
use Shared\Application\Validation\ValidUniqueValue;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidUniqueProductLabel extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ValidUniqueValue(ProductUniqueKey::LABEL),
        ];
    }
}
