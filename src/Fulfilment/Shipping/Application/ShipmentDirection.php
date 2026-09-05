<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application;

enum ShipmentDirection: string
{
    case OUTBOUND = 'outbound';
    case RETURN = 'return';
}
