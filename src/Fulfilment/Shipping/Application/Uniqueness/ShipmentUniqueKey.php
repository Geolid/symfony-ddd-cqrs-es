<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Uniqueness;

enum ShipmentUniqueKey: string
{
    case TRACKING_NUMBER = 'fulfilment.shipping.shipment.tracking_number';
}
