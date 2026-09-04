<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Validation;

use Sales\Buyer\Domain\ValueObject\BuyerUniqueKey;
use Shared\Application\Validation\ValidUniqueValue;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidUniqueBuyerEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ValidUniqueValue(BuyerUniqueKey::EMAIL),
        ];
    }
}
