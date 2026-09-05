<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ManifestDeniedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCancelledShipment(string $shipmentId): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s": it is cancelled.', $shipmentId));
    }

    public static function forUncapturedPayment(string $shipmentId): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s": related payment capture is still pending.', $shipmentId));
    }
}
