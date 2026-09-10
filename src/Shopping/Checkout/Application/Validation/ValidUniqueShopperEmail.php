<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Validation;

use Shared\Application\Validation\ValidUniqueValue;
use Shopping\Checkout\Application\ShopperUniqueKey;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidUniqueShopperEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ValidUniqueValue(ShopperUniqueKey::EMAIL),
        ];
    }
}
