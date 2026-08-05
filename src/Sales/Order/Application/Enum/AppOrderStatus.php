<?php

declare(strict_types=1);

namespace Sales\Order\Application\Enum;

enum AppOrderStatus: string
{
    case PLACED = 'placed';
    case CANCELLED = 'cancelled';
}
