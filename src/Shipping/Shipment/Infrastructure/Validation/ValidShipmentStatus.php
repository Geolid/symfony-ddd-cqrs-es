<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Validation;

use Shared\Infrastructure\Validation\ValidValueObject;
use Shipping\Shipment\Domain\ShipmentStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidShipmentStatus extends Compound
{
    /**
     * Usable inside an attribute argument, unlike a method call — that is why the cases are
     * listed rather than derived through ShipmentStatus::cases().
     */
    public const array VALUES = [
        ShipmentStatus::PENDING->value,
        ShipmentStatus::DISPATCHED->value,
        ShipmentStatus::DELIVERED->value,
    ];

    protected function getConstraints(array $options): array
    {
        return [
            new Assert\Type('string'),
            new Assert\Choice(choices: self::VALUES),
            new ValidValueObject(ShipmentStatus::class, method: 'from'),
        ];
    }
}
