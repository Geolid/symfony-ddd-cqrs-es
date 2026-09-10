<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Manifest\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ManifestDeniedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forCancelledShipment(string $shipmentId): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s": it is cancelled.', $shipmentId));
    }

    public static function forUnpaidOrder(string $shipmentId): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s": the related order is not paid yet.', $shipmentId));
    }
}
