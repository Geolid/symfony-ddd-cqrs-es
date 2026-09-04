<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Validation;

use Finance\Payment\Domain\ValueObject\PaymentReference;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidPaymentReference extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: PaymentReference::MAX_LENGTH),
            new ValidValueObject(PaymentReference::class, method: 'fromString'),
        ];
    }
}
