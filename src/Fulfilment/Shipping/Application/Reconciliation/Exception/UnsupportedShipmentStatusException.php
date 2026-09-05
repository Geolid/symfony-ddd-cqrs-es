<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Reconciliation\Exception;

use Fulfilment\Shipping\Application\ShipmentStatus;
use Shared\Application\Exception\ApplicationExceptionInterface;

final class UnsupportedShipmentStatusException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forStatus(ShipmentStatus $status): self
    {
        return new self(\sprintf('No reconciler supports shipment status "%s".', $status->value));
    }
}
