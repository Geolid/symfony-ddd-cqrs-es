<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Domain\ValueObject\CountryCode;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidCountryCode extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotBlank(),
                new Assert\Choice(callback: static fn (): array => array_column(CountryCode::cases(), 'value')),
                new ValidValueObject(CountryCode::class, method: 'from'),
            ]),
        ];
    }
}
