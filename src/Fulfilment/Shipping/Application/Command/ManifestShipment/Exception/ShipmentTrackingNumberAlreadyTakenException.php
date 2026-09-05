<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\ManifestShipment\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ShipmentTrackingNumberAlreadyTakenException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forTrackingNumber(string $trackingNumber, \Throwable $previous): self
    {
        return new self(
            message: \sprintf('The tracking number "%s" is already assigned to another shipment.', $trackingNumber),
            previous: $previous,
        );
    }
}
