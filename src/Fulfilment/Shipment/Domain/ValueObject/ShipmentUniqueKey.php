<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

enum ShipmentUniqueKey: string
{
    case TRACKING_NUMBER = 'fulfilment.shipment.tracking_number';
}
