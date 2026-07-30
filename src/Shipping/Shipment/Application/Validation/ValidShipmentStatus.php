<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Validation;

use Shared\Application\Language\PublishedLanguageInterface;
use Shared\Application\Validation\ValidValueObject;
use Shipping\Shipment\Application\Language\ShipmentStatuses;
use Shipping\Shipment\Domain\ShipmentStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidShipmentStatus extends Compound implements PublishedLanguageInterface
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Type('string'),
            new Assert\Choice(choices: ShipmentStatuses::ALL),
            new ValidValueObject(ShipmentStatus::class, method: 'from'),
        ];
    }
}
