<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

enum ShipmentUniqueValue: string
{
    case TRACKING_REFERENCE = 'fulfilment.shipment.tracking_reference';
}
