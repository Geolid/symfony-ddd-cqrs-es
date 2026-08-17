<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Validation;

use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Shared\Application\Validation\ValidUniqueValue;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidUniqueCustomerEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ValidUniqueValue(CustomerUniqueKey::EMAIL),
        ];
    }
}
