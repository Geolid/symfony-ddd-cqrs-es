<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Domain\ValueObject\Currency;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidCurrency extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotBlank(),
                new Assert\Choice(callback: Currency::values(...)),
                new ValidValueObject(Currency::class, method: 'from'),
            ]),
        ];
    }
}
