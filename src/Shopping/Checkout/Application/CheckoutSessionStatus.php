<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application;

enum CheckoutSessionStatus: string
{
    case OPEN = 'open';
    case EXPIRED = 'expired';
    case STALE = 'stale';
    case COMPLETED = 'completed';
}
