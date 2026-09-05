<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderState: string
{
    case PLACED = 'placed';
    case CONFIRMED = 'confirmed';
    case PREPARED = 'prepared';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case RETURN_REQUESTED = 'return_requested';
    case RETURNED = 'returned';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';
}
