<?php

declare(strict_types=1);

namespace Sales\Ordering\Application;

enum OrderStatus: string
{
    case CONFIRMED = 'confirmed';
    case PREPARED = 'prepared';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
