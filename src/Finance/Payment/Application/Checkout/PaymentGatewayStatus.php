<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

enum PaymentGatewayStatus: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case DECLINED = 'declined';
    case VOIDED = 'voided';
    case REFUNDING = 'refunding';
    case REFUNDED = 'refunded';
}
