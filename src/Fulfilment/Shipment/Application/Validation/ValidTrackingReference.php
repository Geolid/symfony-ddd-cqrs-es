<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Validation;

use Fulfilment\Shipment\Domain\TrackingReference;
use Shared\Application\Language\PublishedLanguageInterface;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidTrackingReference extends Compound implements PublishedLanguageInterface
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Type('string'),
            new ValidValueObject(TrackingReference::class),
        ];
    }
}
