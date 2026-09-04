<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Validation;

use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidTrackingNumber extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: TrackingNumber::MAX_LENGTH),
            new ValidValueObject(TrackingNumber::class, method: 'fromString'),
        ];
    }
}
