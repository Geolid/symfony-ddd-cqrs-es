<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

enum CarrierGatewayStatus: string
{
    case REQUESTED = 'requested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
}
