<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ShipmentResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forTrackingReference(string $trackingReference): self
    {
        return new self(\sprintf('Shipment tracked under reference "%s" not found.', $trackingReference));
    }
}
