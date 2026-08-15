<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Validation;

use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidTrackingReference extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new Assert\Length(max: 64),
            new ValidValueObject(TrackingReference::class),
        ];
    }
}
