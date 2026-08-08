<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Domain\ValueObject\Money;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidMoney extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotNull(),
            new Assert\Type('int'),
            new Assert\PositiveOrZero(),
            new ValidValueObject(Money::class, method: 'fromCents'),
        ];
    }
}
