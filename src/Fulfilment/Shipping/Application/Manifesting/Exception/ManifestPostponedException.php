<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Manifesting\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ManifestPostponedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forShipmentWithUnpaidOrder(string $shipmentId, string $orderId): self
    {
        return new self(\sprintf('Cannot manifest shipment "%s" yet: order "%s" is not paid yet.', $shipmentId, $orderId));
    }
}
