<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Carrier;

enum CarrierGatewayStatus: string
{
    case REQUESTED = 'requested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case RETURN_DISPATCHED = 'return_dispatched';
    case RETURN_RECEIVED = 'return_received';
}
