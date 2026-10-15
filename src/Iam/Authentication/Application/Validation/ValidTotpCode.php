<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Validation;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidTotpCode extends Compound
{
    public const int LENGTH = 6;

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Sequentially([
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Type('string'),
                new Assert\Regex(\sprintf('/^\d{%d}$/', self::LENGTH)),
            ]),
        ];
    }
}
