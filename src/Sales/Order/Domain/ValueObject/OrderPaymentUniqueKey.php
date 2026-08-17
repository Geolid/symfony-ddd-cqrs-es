<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderPaymentUniqueKey: string
{
    case REFERENCE = 'sales.order.order_payment.reference';
}
