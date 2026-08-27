<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Domain\ValueObject\Address;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidAddress extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\Collection(
                    fields: [
                        'street' => [
                            new Assert\NotBlank(normalizer: 'trim'),
                            new Assert\Length(max: Address::STREET_MAX_LENGTH),
                        ],
                        'postalCode' => [
                            new Assert\NotBlank(normalizer: 'trim'),
                            new Assert\Length(max: Address::POSTAL_CODE_MAX_LENGTH),
                        ],
                        'city' => [
                            new Assert\NotBlank(normalizer: 'trim'),
                            new Assert\Length(max: Address::CITY_MAX_LENGTH),
                        ],
                    ],
                    allowMissingFields: false,
                ),
                new ValidValueObject(Address::class, method: 'of'),
            ]),
        ];
    }
}
