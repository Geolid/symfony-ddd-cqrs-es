<?php

declare(strict_types=1);

namespace Finance\Refund\Application;

enum RefundStatus: string
{
    case INITIATED = 'initiated';
    case REFUNDED = 'refunded';
}
