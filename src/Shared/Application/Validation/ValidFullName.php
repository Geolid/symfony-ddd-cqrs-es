<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Domain\ValueObject\FullName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidFullName extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\Collection(
                    fields: [
                        'firstName' => [
                            new Assert\NotBlank(normalizer: 'trim'),
                            new Assert\Length(max: FullName::MAX_LENGTH),
                        ],
                        'lastName' => [
                            new Assert\NotBlank(normalizer: 'trim'),
                            new Assert\Length(max: FullName::MAX_LENGTH),
                        ],
                    ],
                    allowMissingFields: false,
                ),
                new ValidValueObject(FullName::class, method: 'of'),
            ]),
        ];
    }
}
