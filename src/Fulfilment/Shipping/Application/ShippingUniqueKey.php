<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application;

enum ShippingUniqueKey: string
{
    case TRACKING_NUMBER = 'fulfilment.shipping.shipment.tracking_number';
}
