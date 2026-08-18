<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

enum ShipmentUniqueKey: string
{
    case TRACKING_REFERENCE = 'fulfilment.shipment.tracking_reference';
    case RETURN_TRACKING_REFERENCE = 'fulfilment.shipment.return_tracking_reference';
}
