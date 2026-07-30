<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Validation;

use Ordering\Order\Domain\Money;
use Shared\Application\Language\PublishedLanguageInterface;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidMoney extends Compound implements PublishedLanguageInterface
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Type('int'),
            new Assert\PositiveOrZero(),
            new ValidValueObject(Money::class, method: 'fromCents'),
        ];
    }
}
