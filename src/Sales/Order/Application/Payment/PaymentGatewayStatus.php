<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

enum PaymentGatewayStatus: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case DECLINED = 'declined';
    case VOIDED = 'voided';
    case REFUNDING = 'refunding';
    case REFUNDED = 'refunded';
}
