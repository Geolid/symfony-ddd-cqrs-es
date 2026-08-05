<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Validation;

use Fulfilment\Shipment\Application\Enum\AppShipmentStatus;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentStatus;
use Shared\Application\Validation\ValidValueObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidShipmentStatus extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Type('string'),
            new Assert\Choice(choices: array_column(AppShipmentStatus::cases(), 'value')),
            new ValidValueObject(ShipmentStatus::class, method: 'from'),
        ];
    }
}
