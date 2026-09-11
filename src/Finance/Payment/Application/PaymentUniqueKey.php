<?php

declare(strict_types=1);

namespace Finance\Payment\Application;

enum PaymentUniqueKey: string
{
    case REFERENCE = 'finance.payment.payment.reference';
    case CHECKOUT_SESSION = 'finance.payment.payment.checkout_session';
}
