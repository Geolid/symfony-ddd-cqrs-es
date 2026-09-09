<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Uniqueness;

enum PaymentUniqueKey: string
{
    case REFERENCE = 'finance.payment.payment.reference';
    case CART = 'finance.payment.payment.cart';
}
