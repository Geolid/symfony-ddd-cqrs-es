<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
}
