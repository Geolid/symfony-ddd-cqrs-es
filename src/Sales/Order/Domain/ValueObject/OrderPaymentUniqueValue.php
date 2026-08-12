<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderPaymentUniqueValue: string
{
    case REFERENCE = 'sales.order.order_payment.reference';
}
