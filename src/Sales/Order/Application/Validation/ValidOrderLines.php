<?php

declare(strict_types=1);

namespace Sales\Order\Application\Validation;

use Shared\Application\Language\PublishedLanguageInterface;
use Shared\Application\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidOrderLines extends Compound implements PublishedLanguageInterface
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Type('array'),
            new Assert\Count(min: 1),
            new Assert\All([
                new Assert\Collection([
                    'label' => [new Assert\Type('string'), new Assert\NotBlank(normalizer: 'trim')],
                    'quantity' => [new Assert\Type('int'), new Assert\Positive()],
                    'unitAmountInCents' => new ValidMoney(),
                ]),
            ]),
        ];
    }
}
